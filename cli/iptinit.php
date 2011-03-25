#!/usr/bin/php
<?php
$c=explode('/',__FILE__);$base_path='';
for($i=0;$i<(count($c)-2);$i++)	$base_path.=$c[$i].'/';
include_once("$base_path/config_cli.php");



if(!$CONFIG['route']['ext_ip'])
{
    echo "External ip address NOT configured!\n";
    exit(0);
}

$ipt='iptables';


`echo "1" > /proc/sys/net/ipv4/ip_forward`;
`echo "0" > /proc/sys/net/ipv4/conf/all/log_martians`;

//`./ulogd.init restart`;

`/sbin/modprobe ip_tables`;
`/sbin/modprobe ip_conntrack`;
`/sbin/modprobe iptable_filter`;
`/sbin/modprobe iptable_mangle`;
`/sbin/modprobe iptable_nat`;
`/sbin/modprobe ipt_LOG`;
`/sbin/modprobe ipt_limit`;
`/sbin/modprobe ipt_state`;
if($CONFIG['route']['ulog']['enable'])	`modprobe ipt_ULOG nlbufsiz=800000`;

`$ipt -F`;		// RESET ALL iptables RULES
`$ipt -X`;
`$ipt -t nat -F`;	//RESET nat RULES
`$ipt -P INPUT DROP`;
`$ipt -P OUTPUT ACCEPT`;
`$ipt -P FORWARD DROP`;


// Create chain for bad tcp packets
`$ipt -N bad_tcp_packets`;
`$ipt -A bad_tcp_packets -p tcp ! --syn -m state --state NEW -j DROP`;


// Create separate chains for ICMP, TCP and UDP to traverse
// allowed chain
`$ipt -N allowed`;
`$ipt -A allowed -p TCP --syn -j ACCEPT`;
`$ipt -A allowed -p TCP -m state --state ESTABLISHED,RELATED -j ACCEPT`;
`$ipt -A allowed -p TCP -j DROP`;
`$ipt -A allowed -j ACCEPT`;



// TCP rules
`$ipt -N tcp_packets`;

`$ipt -A tcp_packets -p TCP -s 189.52.17.130 --dport 25 -j DROP`;	// SMTP

// FTP
`$ipt -A tcp_packets -p TCP -s 0/0 --dport 20 -j allowed`;
`$ipt -A tcp_packets -p TCP -s 0/0 --dport 21 -j allowed`;
`$ipt -A tcp_packets -p TCP -s 0/0 --dport 22 -j allowed`;	// SSH
// `$ipt -A tcp_packets -p TCP -s 0/0 --dport 23 -j allowed`;	// TELNET
`$ipt -A tcp_packets -p TCP -s 0/0 --dport 25 -j allowed`;	// SMTP
//`$ipt -A tcp_packets -p TCP -s 0/0 --dport 53 -j allowed`;	// DNS
`$ipt -A tcp_packets -p TCP -s 0/0 --dport 80 -j allowed`;	// HTTP
`$ipt -A tcp_packets -p TCP -s 0/0 --dport 110 -j allowed`;	// POP3
//`$ipt -A tcp_packets -p TCP -s 0/0 --dport 113 -j allowed`;	// ????
`$ipt -A tcp_packets -p TCP -s 0/0 --dport 143 -j allowed`;	// IMAP
`$ipt -A tcp_packets -p TCP -s 0/0 --dport 443 -j allowed`;	// HTTPS
`$ipt -A tcp_packets -p TCP -s 0/0 --dport 2000 -j allowed`;	// SIEVE
//`$ipt -A tcp_packets -p TCP -s 0/0 --dport 3128 -j allowed`;	// ????
//`$ipt -A tcp_packets -p TCP -s 0/0 --dport 3306 -j allowed`;	// ????
//`$ipt -A tcp_packets -p TCP -s 0/0 --dport 631 -j allowed`;	// CUPS Print
// local TCP rules
`$ipt -A tcp_packets -p TCP -s {$CONFIG['route']['lan_range']} --dport 135:139 -j allowed`;	// SAMBA-local
`$ipt -A tcp_packets -p TCP -s {$CONFIG['route']['lan_range']} --dport 445 -j allowed`;		// SAMBA-local
`$ipt -A tcp_packets -p TCP -s {$CONFIG['route']['lan_range']} --dport 631 -j allowed`;		// CUPS
`$ipt -A tcp_packets -p TCP -s {$CONFIG['route']['lan_range']} --dport 8123 -j allowed`;		// lock-site-local

`$ipt -A tcp_packets -p TCP -s {$CONFIG['route']['lan_range']} -j allowed`;		// Пока изнутри всё разрешено

// UDP ports
`$ipt -N udp_packets`;
`$ipt -A udp_packets -p UDP -s 0/0 --destination-port 22 -j ACCEPT`;
`$ipt -A udp_packets -p UDP -s 0/0 --destination-port 53 -j ACCEPT`;
`$ipt -A udp_packets -p UDP -s 0/0 --destination-port 123 -j ACCEPT`;
// Samba - local
`$ipt -A udp_packets -p UDP -s {$CONFIG['route']['lan_range']} --dport 135:139 -j ACCEPT`;
`$ipt -A udp_packets -p UDP -s {$CONFIG['route']['lan_range']} --dport 445 -j ACCEPT`;
`$ipt -A udp_packets -p UDP -s {$CONFIG['route']['lan_range']} -j ACCEPT`;


//`$ipt -A udp_packets -p UDP -s 0/0 --destination-port 5098 -j ACCEPT`;


// ICMP rules
`$ipt -N icmp_packets`;
`$ipt -A icmp_packets -j ACCEPT`;
`$ipt -A icmp_packets -p ICMP -s 0/0 --icmp-type 8 -j ACCEPT`;
`$ipt -A icmp_packets -p ICMP -s 0/0 --icmp-type 11 -j ACCEPT`;
`$ipt -A icmp_packets -p ICMP -s 0/0 --icmp-type 30 -j ACCEPT`;

// Chain BADMAC #
`$ipt -N BADMAC`;
`$ipt -A BADMAC -j DROP`;

//######################################## Chain INPUT ###########################
`$ipt -A INPUT -p tcp -j bad_tcp_packets`;
//`$ipt -A INPUT -p tcp --dport 135:138 -j DROP`;
//`$ipt -A INPUT -p udp --dport 135:138 -j DROP`;
`$ipt -A INPUT -p ALL -m state --state ESTABLISHED,RELATED -j ACCEPT`;
`$ipt -A INPUT -s 127.0.0.0/8 -j ACCEPT`;
`$ipt -A OUTPUT -d 127.0.0.0/8 -j ACCEPT`;
`$ipt -A INPUT -p TCP -j tcp_packets`;
`$ipt -A INPUT -p UDP -j udp_packets`;
`$ipt -A INPUT -p ICMP -j icmp_packets`;


`$ipt -N IN_INET_FORWARD`;
`$ipt -N OUT_INET_FORWARD`;

//######################################## Chain FORWARD #########################
// Samba
`$ipt -A FORWARD -p tcp --dport 135:138 -j DROP`;
`$ipt -A FORWARD -p udp --dport 135:138 -j DROP`;
`$ipt -A FORWARD -p tcp --dport 445 -j DROP`;
`$ipt -A FORWARD -p udp --dport 445 -j DROP`;

// internet account rules

// LOCAL NET FORWARD
`$ipt -A FORWARD -s {$CONFIG['route']['lan_range']} -d {$CONFIG['route']['lan_range']} -j ACCEPT`;

// FROM LOCAL TO INET
`$ipt -A FORWARD -s {$CONFIG['route']['lan_range']} -d 0.0.0.0/0 -j OUT_INET_FORWARD`;
`$ipt -A FORWARD -s 0.0.0.0/0 -d {$CONFIG['route']['lan_range']} -j IN_INET_FORWARD`;




if($CONFIG['route']['ulog']['enable'])
{
	`$ipt -A OUT_INET_FORWARD -p tcp -m multiport --dport {$CONFIG['route']['ulog']['ports']} -j ULOG`;
}

`$ipt -A IN_INET_FORWARD -j ACCEPT`;
`$ipt -A OUT_INET_FORWARD -j ACCEPT`;



//############################################# NAT ##############################

// Ограничения на доступ к ненужным сайтам
if($CONFIG['route']['iplimit']['enable'])
{
$date_time_array = getdate( time() );
if( ($date_time_array['hours']>=$CONFIG['route']['iplimit']['hstart']) && ($date_time_array['hours']<$CONFIG['route']['iplimit']['hend']) )
{
	$res=mysql_query("SELECT * FROM `traffic_denyip`");
	while($nxt=mysql_fetch_row($res))
	{
		if($CONFIG['route']['iplimit']['toport'])
			`$ipt -t nat -A PREROUTING -d $nxt[1] -p tcp -m multiport --dport 80 -j REDIRECT --to-port {$CONFIG['route']['iplimit']['toport']}`;
		else	`$ipt -t nat -A PREROUTING -d $nxt[1] -p tcp -m multiport --dport 80 -j DROP`;
	}
	echo"IP limit set!\n";
}
else echo"IP limit NOT set, now ".$date_time_array['hours']." hours!\n";
}

if($CONFIG['route']['transparent_proxy'])
{
    echo "Transparent proxy start\n";
    `$ipt -t nat -A PREROUTING -d ! {$CONFIG['route']['ext_ip']} -p tcp -m multiport --dport 80 -j REDIRECT --to-port 3128`;
}

`$ipt -t nat -A PREROUTING -d {$CONFIG['route']['ext_ip']} -i {$CONFIG['route']['ext_iface']} -p tcp -m tcp --dport 10012 -j DNAT --to-destination 192.168.1.12:10012`;
`$ipt -t nat -A PREROUTING -d {$CONFIG['route']['ext_ip']} -i {$CONFIG['route']['ext_iface']} -p udp -m udp --dport 10012 -j DNAT --to-destination 192.168.1.12:10012`;
`$ipt -t nat -A PREROUTING -d {$CONFIG['route']['ext_ip']} -i {$CONFIG['route']['ext_iface']} -p tcp -m tcp --dport 3389 -j DNAT --to-destination 192.168.1.2:3389`;
`$ipt -t nat -A PREROUTING -d {$CONFIG['route']['ext_ip']} -i {$CONFIG['route']['ext_iface']} -p tcp -m tcp --dport 3390 -j DNAT --to-destination 192.168.1.45:3389`;
`$ipt -t nat -A PREROUTING -d {$CONFIG['route']['ext_ip']} -i {$CONFIG['route']['ext_iface']} -p tcp -m tcp --dport 4000 -j DNAT --to-destination 192.168.1.45:4000`;
`$ipt -t nat -A PREROUTING -d {$CONFIG['route']['ext_ip']} -i {$CONFIG['route']['ext_iface']} -p tcp -m tcp --dport 636 -j DNAT --to-destination 192.168.1.22:631`;



`$ipt -t nat -A POSTROUTING -s {$CONFIG['route']['lan_range']} -d ! {$CONFIG['route']['lan_range']} -o {$CONFIG['route']['ext_iface']} -j SNAT --to-source {$CONFIG['route']['ext_ip']}`;

echo"All ok!\n";

?>