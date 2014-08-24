// Зависит от jquery!

function number_format( number, decimals, dec_point, thousands_sep ) {  // Format a number with grouped thousands
    // 
    // +   original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +     bugfix by: Michael White (http://crestidg.com)
 
    var i, j, kw, kd, km;
 
    // input sanitation & defaults
    if( isNaN(decimals = Math.abs(decimals)) ){
        decimals = 2;
    }
    if( dec_point == undefined ){
        dec_point = ",";
    }
    if( thousands_sep == undefined ){
        thousands_sep = ".";
    }
 
    i = parseInt(number = (+number || 0).toFixed(decimals)) + "";
 
    if( (j = i.length) > 3 ){
        j = j % 3;
    } else{
        j = 0;
    }
 
    km = (j ? i.substr(0, j) + thousands_sep : "");
    kw = i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands_sep);
    //kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).slice(2) : "");
    kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, 0).slice(2) : "");
 
 
    return km + kw + kd;
}


function PosEditorInit(poslist_setup) {
	var container = document.getElementById(poslist_setup.container);
	var poslist = document.createElement('table');
	var p_sum = document.createElement('div');
	container.appendChild(poslist);
	container.appendChild(p_sum);
	p_sum.className = 'doc_sum_info';

	poslist.base_url = poslist_setup.base_url;
	poslist.editable = poslist_setup.editable;
	poslist.show_column = poslist_setup.columns;
	poslist.auto_price = 0;
	poslist.setup = poslist_setup;
	poslist.p_sum = p_sum;
	
	function init() {
		poslist.id = 'poslist';
		poslist.style.width = '100%';
		poslist.head = document.createElement('thead');
		var head_row = poslist.head.insertRow(0);
		var th = document.createElement('th');
		th.textContent = 'N';
		th.style.width='50px';
		head_row.appendChild(th);
		
		var tmp;
		var i;
		for(i=0;i<poslist_setup.col_names.length;i++) {
			var th = document.createElement('th');
			th.textContent = poslist_setup.col_names[i];
			head_row.appendChild(th);
			switch(poslist_setup.columns[i]) {
				case 'vc':
				case 'name':
				case 'price':
				case 'place':
					tmp = document.createElement('div');
					tmp.className = 'order_button';
					tmp.useFor = poslist_setup.columns[i];
					tmp.addEventListener( 'click', reOrder, false);
					th.appendChild(tmp);
					break;
			}
			
			switch(poslist_setup.columns[i]) {
				case 'sprice':
				case 'price':
				case 'cnt':
				case 'sum':
				case 'place':
				case 'store_cnt':
					th.style.width='80px';
					break;
				case 'vc':
					th.style.width='140px';
					break;
			}
		}
		poslist.appendChild(poslist.head);
		
		if(poslist_setup.fastadd_line && poslist_setup.editable) {
			PladdInit(poslist);
		}
		poslist.body = document.createElement('tbody');
		var body_row = poslist.body.insertRow(0);
		var td = document.createElement('td');
		td.colSpan = i+1;
		td.style.textAlign = 'center';
		td.innerHTML = "<img src='/img/icon_load.gif' alt='Загрузка...'>";
		body_row.appendChild(td);	
		
		
		poslist.appendChild(poslist.body);
	};
	
	function createNameCell(data, row) {
		var td_name = document.createElement('td');
		td_name.className = 'la';
		var textNode = document.createTextNode(data.name);
		td_name.appendChild(textNode);
		if(!data.comm)
			data.comm = '';
		var tmp = document.createElement('br');
		td_name.appendChild(tmp);
		var comment = document.createElement('small');
		textNode = document.createTextNode(data.comm);
		comment.appendChild(textNode);
		td_name.appendChild(comment);
		if(poslist.editable) {
			comment.onclick = function() { poslist.showCommEditor(row); };
		}
		return td_name;
	}
	
	function createPriceCell(data, row) {
		var td_price = document.createElement('td');
		if(data.retail)
			td_price.className = 'retail';
		if(poslist.editable) {
			var input = document.createElement('input');
			input.type = 'text';
			input.name = 'cost';
			input.value = data.cost;
			input.old_value = input.value;
			input.onkeydown = poslist.doInputKeyDown;
			input.onblur = poslist.doInputBlur;
			if(poslist.auto_price)
				input.disabled = 'disabled';
			td_price.appendChild(input);
		}
		else {
			var textNode = document.createTextNode(data.cost);
			td_price.appendChild(textNode);
		}
		return td_price;
	}
	
	function createCntCell(data, row) {
		var td_cnt = document.createElement('td');
		if(poslist.editable) {
			var input = document.createElement('input');
			input.type = 'text';
			input.name = 'cnt';
			input.value = data.cnt;
			input.old_value = input.value;
			input.onkeydown = poslist.doInputKeyDown;
			input.onblur = poslist.doInputBlur;				
			td_cnt.appendChild(input);
		}
		else {
			var textNode = document.createTextNode(data.cnt);
			td_cnt.appendChild(textNode);
		}
		return td_cnt;
	}
	
	function createSumCell(data, row) {
		var td_sum = document.createElement('td');
		if(poslist.editable) {
			var input = document.createElement('input');
			input.type = 'text';
			input.name = 'sum';
			input.value = (data.cost * data.cnt).toFixed(2);
			input.old_value = input.value;
			input.onkeydown = poslist.doInputKeyDown;
			input.onblur = poslist.doInputBlur;	
			if(poslist.auto_price)
				input.disabled = 'disabled';
			td_sum.appendChild(input);
		}
		else {
			var textNode = document.createTextNode((data.cost * data.cnt).toFixed(2));
			td_sum.appendChild(textNode);
		}
		return td_sum;
	}
	
	function createConstCell(value) {
		var td = document.createElement('td');
		var textNode = document.createTextNode(value);
		td.appendChild(textNode);
		return td;
	}
	
	function createGtdCell(value) {
		var td = document.createElement('td');
		var textNode = document.createTextNode(value);
		td.appendChild(textNode);
		td.onclick=poslist.showGTDEditor;
		return td;
	}
	
	function createSnCell(value) {
		var td = document.createElement('td');
		var textNode = document.createTextNode(value);
		td.appendChild(textNode);
		td.onclick=poslist.showSnEditor;
		return td;
	}
	
	function reOrder(event) {
		$.ajax({
				type:   'GET',
			url:    poslist.base_url,
			data:   'peopt=jorder&by=' + event.target.useFor,
			success: function(msg) { poslist.refresh() },
			error:   function() { jAlert('Ошибка!','Сортировка наименований',{},'icon_err'); },
			});
	}
	
	poslist.refresh = function() {
		$.ajax({
			type:   'GET',
			url:    poslist.base_url,
			data:   'peopt=jget',
			success: function(msg) { poslist.tBodies[0].innerHTML=''; rcvDataSuccess(msg); },
			error:   function() { jAlert('Ошибка соединения!','Получение списка товаров',null,'icon_err'); }
		});
	};

	poslist.doInputKeyDown = function(e) {
		var e = e || window.event;
		if (e.keyCode == 40) {
			var row = this.parentNode.parentNode.nextSibling;
			if (row == null)
				return false;
			if (row.nodeType != 1)
				return false;
			var inputs = row.getElementsByTagName('input');
			for (var i = 0; i < inputs.length; i++) {
				if (inputs[i].name == this.name)
					inputs[i].focus();
			}
			return false;
		}
		else if(e.keyCode==38) {
			var row = this.parentNode.parentNode.previousSibling;
			if (row.nodeType != 1)
				return false;
			var inputs = row.getElementsByTagName('input');
			for (var i = 0; i < inputs.length; i++)	{
				if (inputs[i].name == this.name)
					inputs[i].focus();
			}
			return false;
		}
		else if (e.keyCode == 37 && e.shiftKey == true) {
			var row = this.parentNode.parentNode;
			if (row.nodeType != 1)
				return false;
			var inputs = row.getElementsByTagName('input');
			for (var i = 0; i < inputs.length; i++) {
				if (this.name == 'cnt' && inputs[i].name == 'cost')
					inputs[i].focus();
				else if (this.name == 'sum' && inputs[i].name == 'cnt')
					inputs[i].focus();
			}
			return false;
		}
		else if (e.keyCode == 39 && e.shiftKey == true)	{
			var row = this.parentNode.parentNode;
			if (row.nodeType != 1)
				return false;
			var inputs = row.getElementsByTagName('input');
			for (var i = 0; i < inputs.length; i++) {
				if (this.name == 'cost' && inputs[i].name == 'cnt')
					inputs[i].focus();
				else if (this.name == 'cnt' && inputs[i].name == 'sum')
					inputs[i].focus();
			}
			return false;
		}
		//return false
	}

	poslist.doInputBlur=function() {
		if(this.old_value==this.value)	return;
		var line=this.parentNode.parentNode;
		line.className='el';
		$.ajax({
			type:   'GET',
		       url:    poslist.base_url,
		       data:   'peopt=jup&type='+this.name+'&value='+this.value+'&line_id='+line.lineIndex,
		       success: function(msg) { rcvDataSuccess(msg); },
		       error:   function() { jAlert('Ошибка соединения!','Обновление данных',function() {},'icon_err'); },
		});
	}
	
	poslist.AddLine = function(data) {
		var row_cnt = poslist.tBodies[0].rows.length;
		var row = poslist.tBodies[0].insertRow(row_cnt);
		row.lineIndex = data.line_id;
		row.id = 'posrow' + data.line_id;
		
		row.sklad_cnt = Number(data.sklad_cnt);
		row.comm = data.comm;

		row.ondblclick = row.oncontextmenu = function(event) {
			var menu = ShowPosContextMenu(event ,data.pos_id, '');
			if(poslist.editable) {
				var menudiv=document.createElement('div');
				menudiv.innerHTML='Правка комментария';
				menudiv.onclick=function() { poslist.showCommEditor(row); };
				menu.appendChild(menudiv);
			}
			return false;
		};
		
		var fragment = document.createDocumentFragment();
		
		// N линии и кнопка *удалить*
		var td_id = document.createElement('td');
		var textNode = document.createTextNode(row_cnt+1);
		td_id.appendChild(textNode);
		if(poslist.editable) {
			var img_del = document.createElement('img');
			img_del.src = '/img/i_del.png';
			img_del.className = 'pointer';
			img_del.id = 'del'+row.lineIndex;	// Это, вероятно, лишенее
			img_del.onclick = poslist.doDeleteLine;
			td_id.appendChild(img_del);
			if(Number(data.cnt)>Number(data.sklad_cnt))
				row.style.color="#f00";
		}
		fragment.appendChild(td_id);
		
		var i;
		for(i=0;i<poslist.show_column.length;i++) {
			switch(poslist.show_column[i]) {
				case 'vc':
					fragment.appendChild(createConstCell(data.vc));	
					break;
				case 'name':
					fragment.appendChild(createNameCell(data, row));	
					break;
				case 'sprice':
					fragment.appendChild(createConstCell(data.scost));	
					break;
				case 'price':
					fragment.appendChild(createPriceCell(data, row));	
					break;
				case 'cnt':
					fragment.appendChild(createCntCell(data, row));	
					break;
				case 'sum':
					fragment.appendChild(createSumCell(data, row));	
					break;
				case 'store_cnt':
					fragment.appendChild(createConstCell( Math.round(data.sklad_cnt*100)/100));	
					break;
				case 'place':
					fragment.appendChild(createConstCell(data.place));	
					break;
				case 'gtd':
					fragment.appendChild(createGtdCell(data.gtd));	
					break;
				case 'sn':
					fragment.appendChild(createSnCell(data.gtd));	
					break;
				default:fragment.appendChild(createConstCell('???'+poslist.show_column[i]+'???'));	
			}
		}

		row.appendChild(fragment);
	};

	poslist.UpdateLine=function(data) {
		var line=document.getElementById('posrow'+data.line_id)
		var inputs=line.getElementsByTagName('input')
		for(var i=0;i<inputs.length;i++)
		{
			//alert(inputs[i].name)
			if(inputs[i].name=='cnt')	inputs[i].value=data.cnt
			else if(inputs[i].name=='cost') {
				inputs[i].value=Number(data.cost).toFixed(2);
				if(data.retail)
					inputs[i].parentNode.className = 'retail';
				else	inputs[i].parentNode.className = '';
				if(poslist.auto_price)
					inputs[i].disabled = 'disabled';
				else	inputs[i].disabled = '';
			}
			else if(inputs[i].name=='sum'){
				inputs[i].value = Number(data.cost*data.cnt).toFixed(2);
				if(poslist.auto_price)
					inputs[i].disabled = 'disabled';
				else	inputs[i].disabled = '';
			}
			inputs[i].old_value=inputs[i].value
		}
		if(Number(data.cnt)>Number(line.sklad_cnt))	line.style.color="#f00";
		else						line.style.color="inherit";
		line.className='hl'
		if(line.timeout)	window.clearTimeout(line.timeout)
		line.timeout=window.setTimeout(function(){line.className='';}, 2000)
	}
	
	poslist.RemoveLine=function(line_id)
	{
		var line=document.getElementById('posrow'+line_id)
		line.parentNode.removeChild(line)
	}

	poslist.doDeleteLine=function()
	{
		var line=this.parentNode.parentNode;
		if(confirm('Удалить строку '+line.lineIndex+'?')) {
			$('#'+line.id).addClass('dl')
			$.ajax({
				type:   'GET',
				url:    poslist.base_url,
				data:   'peopt=jdel&line_id='+line.lineIndex,
				success: function(msg) { rcvDataSuccess(msg); },
				error:   function() { jAlert('Ошибка соединения!','Получение списка товаров',null,'icon_err'); },
			});
		}
	}
	
	poslist.updateSumInfo = function(json) {
		var str = '';
		if(json.sum)
			str = 'Итого: <b>' + (poslist.tBodies[0].rows.length) + '</b> поз. на сумму <b>' + Number(json.sum).toFixed(2) + '</b> руб. ';
		if(json.price_name)
			str += ' Цена: <b>' + json.price_name + '</b>.';

		if(json.doc_sum!=json.sum) {
			var skid = json.base_sum - json.sum;
			if(skid>0) {
				var skid_p = skid.toFixed(2);
				var skid_pp = ((json.base_sum - json.sum)/json.base_sum*100).toFixed(1);
				str += ' Cкидка: <b>' + skid_p + '</b> руб. <b>( ' + skid_pp + '% )</b>';
			}
		}

		if(json.nbp_info) {
			str += '<br>До разовой спец.цены <b>' + json.nbp_info.name + '</b> осталось <b>'
				+ (json.nbp_info.incsum).toFixed(2) + '</b> руб.';
		}

		if(json.npp_info) {
			str += '<br>До накопительной спец.цены <b>' + json.npp_info.name + '</b> осталось <b>' 
				+ (json.npp_info.incsum).toFixed(2) + '</b> руб.';
		}

		p_sum.innerHTML = str;
	}

	function rcvDataSuccess(msg) {
		try
		{
			var json=eval('('+msg+')');
			var str = '';
			if(json.response==0)
				jAlert(json.message,"Ошибка", null, 'icon_err');
			else if(json.response=='err')
				jAlert(json.message,"Ошибка", null, 'icon_err');
			else if(json.response=='loadlist')
			{
				poslist.auto_price = json.auto_price;
				for(var i=0;i<json.content.length;i++)
					poslist.AddLine(json.content[i]);
				poslist.updateSumInfo(json);
			}
			else if(json.response=='update') {
				poslist.auto_price = json.auto_price;
				if(json.update_line)
					poslist.UpdateLine(json.update_line);
				if(json.update_list) {
					for(var i=0;i<json.update_list.length;i++)
						poslist.UpdateLine(json.update_list[i]);
				}
				poslist.updateSumInfo(json);
			}
			else if(json.response=='add') {	// Вставка строки
				poslist.AddLine(json.line);
				poslist.updateSumInfo(json);
			}
			else if(json.response==5) {
				poslist.RemoveLine(json.remove.line_id)
				poslist.updateSumInfo(json);
			}
			else jAlert("Обработка полученного сообщения (" + json.response + ") не реализована!", "Загрузка данных", null,  'icon_err');
		}
		catch(e)
		{
			jAlert("Критическая ошибка!<br>Если ошибка повторится, уведомите администратора о том, при каких обстоятельствах возникла ошибка!"+
			"<br><br><i>Информация об ошибке</i>:<br>"+e.name+": "+e.message+"<br>"+msg, "Загрузка данных", null,  'icon_err');
		}
	}

	// Окно ввода ГТД
	poslist.showGTDEditor=function(event)
	{
		var poslist_line=event.target.parentNode
		var line=poslist_line.lineIndex
		jPrompt("Введите номер ГТД", event.target.innerHTML, "Редактирование документа", function(val)
		{
			event.target.innerHTML=val
			line.className='el'
			$.ajax({
				type:   'GET',
				url:    poslist.base_url,
				data:   'peopt=jup&type=gtd&value='+val+'&line_id='+line,
				success: function(msg) { rcvDataSuccess(msg); },
				error:   function() { jAlert('Ошибка соединения!','Обновление данных',function() {},'icon_err'); },
			});
		})
	}

	// Окно ввода коментария
	poslist.showCommEditor=function(poslist_line)
	{
 		var line=poslist_line.lineIndex
 		jPrompt("Введите комментарий", poslist_line.comm, "Редактирование документа", function(val)
		{
			if(val)
			{
				poslist_line.comm=val
				var smalltag=poslist_line.getElementsByTagName('small')
				for(var i=0;i<smalltag.length;i++)
				{
					smalltag[i].innerHTML=val
				}
				poslist_line.className='el'
				$.ajax({
				type:   'GET',
				url:    poslist.base_url,
				data:   'peopt=jup&type=comm&value='+encodeURIComponent(val)+'&line_id='+line,
				success: function(msg) { rcvDataSuccess(msg); },
				error:   function() { jAlert('Ошибка соединения!','Обновление данных',function() {},'icon_err'); },
				});
			}
		})
	}

	// Редактор серийных номеров
	poslist.showSnEditor=function(event)
	{
		var poslist_line=event.target.parentNode
		var line=poslist_line.lineIndex
		var sn_cnt=0
		$.ajax({
			type:   'GET',
			url:    poslist.base_url,
			data:   'peopt=jsn&a=l&line='+line,
			success: function(msg) { ShowSnEditorSuccess(msg); },
			error:   function() { jAlert('Ошибка!','Редактор серийного номера',{},'icon_err'); },
		});

		function ShowSnEditorSuccess(msg)
		{
			var json=eval('('+msg+')')
			if(json.response=='sn_list')
			{
				var dialog="<div style='width: 300px; height: 200px; border: 1px solid #ccc; overflow: auto;'><table width='100%' id='sn_list'><tr><td style='width: 20px'><td>"
				for(var i=0;i<json.list.length;i++)
				{
					if(! json.list[i])	continue
					dialog+="<tr id='snl"+json.list[i].id+"'><td><img src='/img/i_del.png' alt='Удалить' id='sndel|"+json.list[i].id+"'></td><td>"+json.list[i].sn+"</td></tr>"
					sn_cnt++;
				}
				dialog+="</table></div><input type='text' name='sn' id='sn'><button type='button' id='btn_sn_add'>&gt;&gt;</button>"

				jAlert(dialog,"Редактор серийных номеров", function() {
					var sn_cell=document.getElementById('sn'+line)
					sn_cell.innerHTML=sn_cnt

				});

				for(var i=0;i<json.list.length;i++)
				{
					if(! json.list[i])	continue
					var img_del=document.getElementById('sndel|'+json.list[i].id)
					img_del.onclick=SnDel
				}

				document.getElementById('btn_sn_add').onclick=snAdd

				$("#sn").autocomplete("/doc.php", {
					delay:300,
					minChars:1,
					matchSubset:1,
					autoFill:false,
					selectFirst:true,
					matchContains:1,
					cacheLength:10,
					maxItemsToShow:15,
					extraParams:{'mode':'srv','peopt':'snp', 'doc': '1', 'pos': line}
				});

				document.getElementById('sn').onkeyup=function(event)
				{
					if(event.keyCode==13)
					{
						snAdd(event)
					}
				}

			}
			else	jAlert(json.message,"Ошибка", {}, 'icon_err')
		}

		function SnDel(event)
		{
			var line=this.id.split('|')
			line=line[1]
			var row_to_remove=this.parentNode.parentNode

			$.ajax({
				type:   'GET',
				url:    poslist.base_url,
				data:   'peopt=jsn&a=d&line='+line,
				success: function(msg) {
					var json=eval('('+msg+')')
					if(json.response=='deleted')
					{
						row_to_remove.parentNode.removeChild(row_to_remove)
						sn_cnt--;
					}
					else
					{
						alert(json.message)
					}
				},
				error:   function() { jAlert('Ошибка!','Редактор серийного номера',{},'icon_err'); },
			});

		}

		function snAdd(event)
		{
			var sn=document.getElementById("sn");
			$.ajax({
				type:   'GET',
				url:    poslist.base_url,
				data:   'peopt=sns&pos='+line+'&sn='+sn.value,
				success: function(msg) { DocAddSnSuccess(msg); },
				error:   function() { jAlert('Ошибка!','Добавление серийного номера',{},'icon_err'); },
			});

		}

		function DocAddSnSuccess(msg)
		{
			try
			{
				var json=eval('('+msg+')')
				if(json.response==0)
					jAlert(json.message,"Ошибка", {}, 'icon_err')
				else if(json.response==1)	// Добавлено
				{
					var sn_list=document.getElementById("sn_list")
					var sn=document.getElementById("sn")
					var row=document.createElement('tr')
					row.id='snl'+json.sn_id
					row.innerHTML="<td><img src='/img/i_del.png'  id='sndel|"+json.sn_id+"'></td><td>"+json.sn+"</td>"
					sn_list.appendChild(row)
					var img_del=document.getElementById('sndel|'+json.sn_id)
					img_del.onclick=SnDel
					sn_cnt++;
					sn.value=''
				}
			}
			catch(e)
			{
				jAlert("Критическая ошибка!<br>Если ошибка повторится, уведомите администратора о том, при каких обстоятельствах возникла ошибка!"+
				"<br><br><i>Информация об ошибке</i>:<br>"+e.name+": "+e.message, "Добавление серийного номера",function() {},  'icon_err');
			}
		}
	}

	// Строка быстрого добавления наименований
	function PladdInit(poslist) {
		poslist.footer = document.createElement('tfoot');
		poslist.appendChild(poslist.footer);
		var pladd = poslist.footer.insertRow(0);
		pladd.id = 'pladd';
		var tmp;
		var tab_index = 1;
		var last_input_name = 'nothhing';	// Для обработки enter на последнем поле
		function createInput(useFor) {
			var input = document.createElement('input');
			input.useFor = useFor;
			input.autocomplete = 'off';
			input.addEventListener( 'keyup', KeyUp, false);
			input.tabIndex = tab_index++;
			last_input_name = useFor;
			return input;
		}

		var i;
		var input_id;
		var input_vc;
		var input_name;
		var input_price;
		var input_cnt;
		var cell_sum;
		var cell_store_cnt;
		var cell_sprice;

		// ID
		tmp = document.createElement('td');
		input_id = createInput('id');
		tmp.appendChild(input_id);
		pladd.appendChild(tmp);	

		for(i=0;i<poslist.show_column.length;i++) {
			tmp = document.createElement('td');
			switch(poslist.show_column[i]) {
				case 'vc':
					input_vc = createInput('vc');
					tmp.appendChild(input_vc);
					break;
				case 'name':
					input_name = createInput('name');
					tmp.appendChild(input_name);
					break;
				case 'price':
					input_price = createInput('price');
					tmp.appendChild(input_price);
					break;
				case 'cnt':
					input_cnt = createInput('cnt');
					input_cnt.addEventListener( 'keydown', KeyDown, false);
					tmp.appendChild(input_cnt);	
					break;
				case 'sum':
					cell_sum = tmp;
					break;
				case 'store_cnt':
					cell_store_cnt = tmp;
					break;
				case 'sprice':
					cell_sprice = tmp;
					break;
				default:;	
			}
			
			pladd.appendChild(tmp);	
		}
		if(input_name) {
			function nameFormat (row, i, num) {
				var result = row[0] + "<em class='qnt'>произв. " +
				row[2] + ", код: "+ row[3] + "</em> ";
				return result;
			}

			function nameselectItem(li) {
				if( li == null ) var sValue = "Ничего не выбрано!";
				else if( !!li.extra ) var sValue = li.extra[0];
				input_id.value=sValue;
				input_vc.value=li.extra[2];
				input_price.value=0.5;
				input_cnt.value=1;
				input_name.focus();
				pladd.doRefresh()
			}

			$(input_name).autocomplete("/docs.php", {
				delay:300,
				minChars:1,
				matchSubset:1,
				autoFill:false,
				selectFirst:true,
				matchContains:1,
				cacheLength:10,
				maxItemsToShow:20,
				formatItem:nameFormat,
				onItemSelect:nameselectItem,
				extraParams:{'l':'sklad','mode':'srv','opt':'ac'}
			});
		}

		if(input_vc) {
			function vcFormat (row, i, num) {
				var result = row[0];
				return result;
			}

			function vcselectItem(li) {

				if( li == null ) var sValue = "Ничего не выбрано!";
				else if( !!li.extra ) var sValue = li.extra[0];
				if(input_id)
					input_id.value=sValue;
				if(input_name)
					input_name.value=li.extra[2];
				//input_price.value=0.5;
				//input_cnt.value=1;
				
				input_vc.focus();

				pladd.doRefresh()
			}

			$(input_vc).autocomplete("/docs.php", {
				delay:300,
				minChars:1,
				matchSubset:1,
				autoFill:false,
				selectFirst:true,
				matchContains:1,
				cacheLength:10,
				maxItemsToShow:20,
				formatItem:vcFormat,
				onItemSelect:vcselectItem,
				extraParams:{'l':'sklad','mode':'srv','opt':'acv'}
			});
		}

		function AddData() {
			var cnt = 1;
			if(input_cnt)
				cnt = input_cnt.value;
			var price = 1;
			if(input_price)
				price = input_price.value;
			$.ajax({
				type:   'GET',
				url:    poslist.base_url,
				data:   'peopt=jadd&pe_pos='+input_id.value+'&cnt='+cnt+'&cost='+price,
				success: function(msg) { AddDataSuccess(msg); },
				error:   function() { jAlert('Ошибка соединения!','Добавление наименования',null,'icon_err'); },
			});
		}

		function AddDataSuccess(msg)
		{
			try
			{
				var json=eval('('+msg+')');
				if(json.response==0)
					jAlert(json.message,"Ошибка", {}, 'icon_err');
				else if(json.response=='add')	// Вставка строки
				{
					poslist.AddLine(json.line);
					poslist.updateSumInfo(json);
					pladd.Reset();
				}
				else if(json.response=='update')
				{
					poslist.UpdateLine(json.update_line);
					poslist.updateSumInfo(json);
					pladd.Reset();
				}
				else jAlert("Обработка полученного сообщения не реализована<br>"+msg, "Вставка строки в документ", null,  'icon_err');
			}
			catch(e)
			{
				jAlert("Критическая ошибка!<br>Если ошибка повторится, уведомите администратора о том, при каких обстоятельствах возникла ошибка!"+
				"<br><br><i>Информация об ошибке</i>:<br>"+e.name+": "+e.message+"<br>"+msg, "Вставка строки в документ", null,  'icon_err');
			}
		}

		pladd.Reset = function() {
			input_id.value = '';
			if(input_vc)
				input_vc.value = '';
			if(input_name)
				input_name.value = '';
			if(input_price)
				input_price.value = '';
			if(input_cnt)
				input_cnt.value = '';
			if(cell_sum)
				cell_sum.innerHTML = '';
			if(cell_store_cnt)
				cell_store_cnt.innerHTML = '';
			if(cell_sprice)
				cell_sprice.innerHTML = '';
			if (input_vc)
				input_vc.focus();
			else	input_id.focus();
			pladd.className = '';
		};

		pladd.doRefresh = function() {
			if (parseInt(input_id.value) == 0 || parseInt(input_id.value).toString() == 'NaN')
				return;
			pladd.className = 'process';
			$.ajax({
				type: 'GET',
				url: poslist.base_url,
				data: 'peopt=jgpi&pos=' + parseInt(input_id.value),
				success: function(msg) {
					pladd.doRefreshSuccess(msg);
				},
				error: function() {
					jAlert('Ошибка соединения!', 'Автодополнение по коду', null, 'icon_err');
					pladd.className = '';
				}
			});
		};

		pladd.doRefreshSuccess = function(msg) {
			try {
				var json = eval('(' + msg + ')');
				if (json.response == 0)
					jAlert(json.message, "Ошибка", {}, 'icon_err');
				else if (json.response == 3) {	// Вставка строки

					pladd.Refresh(json.data)
				}
				else jAlert("Обработка полученного сообщения не реализована<br>" + msg, "Получение информации о позиции", null, 'icon_err');
			}
			catch (e) {
				jAlert("Критическая ошибка!<br>Если ошибка повторится, уведомите администратора о том, при каких обстоятельствах возникла ошибка!" +
					"<br><br><i>Информация об ошибке</i>:<br>" + e.name + ": " + e.message + "<br>" + msg, "Автодополнение", null, 'icon_err');
			}
			pladd.className = '';
		}

		pladd.Refresh = function(data) {
			if (input_vc)
				input_vc.value = data.vc;
			if(input_name)
				input_name.value = data.name;
			if(input_price)
				input_price.value = data.cost;
			if(input_cnt)
				input_cnt.value = data.cnt;
			if(cell_sum)
				cell_sum.innerHTML = data.cost * data.cnt;
			if(cell_store_cnt)
				cell_store_cnt.innerHTML = data.sklad_cnt;
			if(cell_sprice)
				cell_sprice.innerHTML = data.scost;
			if (data.line_id > 0)
				pladd.className = 'error';
		}

		function KeyUp(_e) {
			var e = _e||window.event;
			if(e.keyCode==13) {
				if(e.target.useFor == last_input_name)
					AddData();
				else {
					var td = e.target.parentNode.nextSibling;
					while (td.nextSibling) {
						if (td.nodeType != 1) {
							td = td.nextSibling;
							continue;
						}
						if(td.firstChild) {
							if (td.firstChild.tagName == 'INPUT') {
								td.firstChild.focus();
								td.firstChild.select();
								return;
							}
						}
						td = td.nextSibling;
					}
				}
			}
			//	AddData();
			if( (e.target.useFor == 'price' || e.target.useFor == 'cnt') && cell_sum && input_price && input_cnt)
				cell_sum.innerHTML = parseFloat(input_price.value)*parseFloat(input_cnt.value);
			if( e.target.useFor == 'id') {
				if( parseInt(input_id.value) != input_id.old_value ) {
					input_id.old_value = parseInt(input_id.value);
					pladd.doRefresh()
				}
			}
		}

		function KeyDown(_e){
			var e = _e||window.event;
			if(e.keyCode==9 && this.useFor==last_input_name)
				return false;
		}

		input_id.old_value = 0;
		pladd.Reset();
	}

	// Callback для внешнего управления виджетом, например из skladlist
	poslist.exec = function(command, data) {
		switch(command) {
			case 'sel_for_add': // Выбрано наименование для добавления
				$.ajax({
					type:   'GET',
					url:    poslist_setup.base_url,
					data:   'peopt=jadd&pe_pos='+data.id+'&cost='+data.price+'&cnt='+data.cnt,
					success: function(msg) { rcvDataSuccess(msg); },
					error:   function() { jAlert('Ошибка соединения!','Добавление наименования',null,'icon_err'); },
				});
			break
		}
	}

	init();
	poslist.refresh();
	var skladview = SkladViewInit(poslist_setup, poslist.exec);

	if(!poslist.editable)
		skladview.style.display = 'none';

	return poslist;
}



// Блок со списком складской номенклатуры
function SkladViewInit(setup, callback) {
	var container = document.getElementById(setup.store_container);
	var skladlist = document.getElementById('sklad_list');
	var groupdata_cache = new Array();
	var old_hl = 0;
	//skladview.show_column = new Array();
	//skladlist.needDialog = 0;
	var col_cnt = 0;
	
	
	function init() { //< NEW
		var tmp, tmp2;
		var left_block = document.createElement('div');
		var right_block = document.createElement('div');
		container.className = 'storeview_container';
		left_block.className = 'storeview_left';
		right_block.className = 'storeview_right';
		
		container.filter_input = document.createElement('input');
		container.filter_input.placeholder = 'Глобальный фильтр...';
		container.filter_input.addEventListener('keydown', filterInputKeydown, false);
		left_block.appendChild(container.filter_input);
		
		container.gl_block = document.createElement('div');		
		container.gl_block.addEventListener('click', tree_toggle, false);
		left_block.appendChild(container.gl_block);
		
		tmp = document.createElement('thead');
		initTableHead(tmp);
		
		tmp2 = document.createElement('table');
		tmp2.appendChild(tmp);
		
		skladlist = document.createElement('tbody');
		tmp2.appendChild(skladlist);
		
		right_block.appendChild(tmp2);
		
		container.appendChild(left_block);
		container.appendChild(right_block);
		
		tmp = document.createElement('div');
		tmp.className='clear'
		container.appendChild(tmp);
		
		$.ajax({
			type:   'GET',
			url:    setup.base_url,
			data:   'peopt=jgetgroups',
			success: function(msg) { rcvDataSuccess(msg); },
			error:   function() { jAlert('Ошибка соединения!','Получение списка групп',null,'icon_err'); },
		});
	}
	
	function initTableHead(head) { //< NEW
		var tmp;
		while(head.firstChild) {
			head.removeChild(head.firstChild);
		}
		var head_row = head.insertRow(0);
		var th = document.createElement('th');
		th.textContent = 'id';
		th.style.width='60px';
		head_row.appendChild(th);
		
		for(i=0;i<setup.store_columns.length;i++) {
			th = document.createElement('th');
			th.textContent = setup.store_columns[i];
//			switch(setup.store_columns[i]) {
//				case 'price':
//				case 'place':
//				case 'store_cnt':
//				case 'allcnt':
//					th.style.width='80px';
//					break;
//			}
			
			head_row.appendChild(th);
			// TODO
//			tmp = document.createElement('div');
//			tmp.className = 'order_button';
//			tmp.useFor = poslist_setup.columns[i];
//			tmp.addEventListener( 'click', reOrder, false);
//			th.appendChild(tmp);
		}
		col_cnt = i+1;
	}
	
	function rebuildGroupList(data) {  //< NEW
		container.gl_block.innerHTML = '';
		
		function appendNode(dom_node, data_node, root) {
			var i;
			if(data_node.length) {
				var ul = document.createElement('ul');
				ul.className = 'Container';
				for(i=0;i<data_node.length;i++) {
					var li = document.createElement('li');
					li.className = 'Node';
					
					if(i == data_node.length-1)
						li.className += ' IsLast';
					if(root)
						li.className += ' IsRoot';	
					
					var d_e = document.createElement('div');
					d_e.className = 'Expand';
					li.appendChild(d_e);
					var d_c = document.createElement('div');
					d_c.className = 'Content';
					d_c.textContent = data_node[i].name;
					d_c.forId = data_node[i].id;
					d_c.addEventListener('click', getGroupData, false);
					d_c.style.cursor='pointer';
					li.appendChild(d_c);
					
					if(data_node[i].childs.length) {
						li.className += ' ExpandClosed';
						appendNode(li, data_node[i].childs, 0);
					}
					else	li.className += ' ExpandLeaf';

					ul.appendChild(li);
				}
				dom_node.appendChild(ul);
			}
		}
		
		appendNode(container.gl_block, data, 1);
	}
	
	function filterInputKeydown(event) {
		if(event.target.timer)
			window.clearTimeout(event.target.timer);
		event.target.timer = window.setTimeout(function(){getSearchResult(event)}, 1000);
	}

	function getGroupData(event) {
		if (old_hl)
			old_hl.style.backgroundColor = '';
		event.target.style.backgroundColor = '#ffb';		
		old_hl = event.target;
		
		var group = event.target.forId;
		if (groupdata_cache[group])
			rcvDataSuccess(groupdata_cache[group]);
		else	skladlist.innerHTML = "<tr><td colspan='20' style='text-align: center;'><img src='/img/icon_load.gif' alt='Загрузка...'></td></tr>";
		
		$.ajax({
			type: 'GET',
			url: setup.base_url,
			data: 'peopt=jsklad&group_id=' + group,
			success: function(msg) {
				groupdata_cache[group] = msg;
				rcvDataSuccess(msg);
			},
			error: function() {
				jAlert('Ошибка соединения!', 'Получение содержимого группы', null, 'icon_err');
			},
		});
		return false;
	}

	function getSearchResult(event) {
		if(old_hl)	old_hl.style.backgroundColor = '';
		old_hl = 0;
		s_str = event.target.value;
		if(s_str=='')	return;
		skladlist.innerHTML = "<tr><td colspan='20' style='text-align: center;'><img src='/img/icon_load.gif' alt='Загрузка...'></td></tr>"
		$.ajax({
			type:   'GET',
			url:    setup.base_url,
			data:   'peopt=jsklads&s='+encodeURIComponent(s_str),
			success: function(msg) { rcvDataSuccess(msg); },
			error:   function() { jAlert('Ошибка соединения!','Получение содержимого группы',null,'icon_err'); },
		});
		return false
	}

// Ячейки
	function createConstCell(value) {
		var td = document.createElement('td');
		var textNode = document.createTextNode(value);
		td.appendChild(textNode);
		return td;
	}
	
	function createHTMLCell(value) {
		var td = document.createElement('td');
		td.innerHTML=value;
		return td;
	}

	function AddLine(data) {
		var row_cnt = skladlist.rows.length;
		var row = skladlist.insertRow(row_cnt);
		
		
		if(data.id != 'header') {
			row.lineIndex = data.id;
			row.id = 'skladrow' + data.id;
			row.data = data;
			row.className = 'pointer';
			row.style.textAlign = 'right';
			if(setup.editable)
				row.addEventListener( 'click', clickRow, false);
			row.addEventListener( 'contextmenu', contextMenu, false);
			
			var fragment = document.createDocumentFragment();
		
			// N линии и кнопка *удалить*
			var td = document.createElement('td');
			var textNode = document.createTextNode(row_cnt+1);
			td.appendChild(textNode);
			fragment.appendChild(td);

			var i;
			for(i=0;i<setup.store_columns.length;i++) {
				var cellname = setup.store_columns[i];
				var value = data[cellname];
				if(value==null)
					value = '';
				switch(cellname) {
					case 'vc':
					case 'name':
					case 'vendor':
						td = fragment.appendChild(createConstCell(value));
						td.style.textAlign = 'left';
						break;
					case 'place':
					case 'type':
					case 'd_int':
					case 'd_ext':
					case 'size':
					case 'mass':	
						fragment.appendChild(createConstCell(value));	
						break;
					case 'transit':
					case 'reserve':
					case 'offer':
						if(value==0)
							value = '';
						td = fragment.appendChild(createConstCell(value));
						td.className = cellname;
						break;
					case 'price':
						if(value==0)
							value = '';
						else	value = number_format(value, 2, '.', '\'');
						td = fragment.appendChild(createHTMLCell(value));
						if(data.price_cat)
							td.className = data.price_cat;
						break;
					case 'liquidity':
						if(value==0)
							value = '';
						else	value = number_format(value, 2, '.', '\'');
						td = fragment.appendChild(createHTMLCell(value));
						break;
					case 'cnt':
					case 'allcnt':
						if(value==0)
							value = '';
						else	value = number_format(value, 3, '.', '\'');
						fragment.appendChild(createHTMLCell(value));
						break;
					default:fragment.appendChild(createConstCell('???'+cellname+'???'));	
				}
			}

			row.appendChild(fragment);
		}
		else {
			var th = document.createElement('th');
			th.colSpan = col_cnt;
			th.className = 'searchinfo';
			var textNode = document.createTextNode(data.name);
			th.appendChild(textNode);
			row.appendChild(th);
		}
		
	}
	
	function contextMenu(event) {
		var line_id = 0;
		if(event.target.lineIndex)
			line_id = event.target.lineIndex;
		if(event.target.parentNode.lineIndex)
			line_id = event.target.parentNode.lineIndex;
		if(line_id)
			ShowPosContextMenu(event, line_id, '');
		event.preventDefault();
		return false;
	}

	function clickRow(event)
	{
		
		var line = 0;
		if(event.target.tagName=='TR')
			line = event.target;
		else if(event.target.parentNode.tagName=='TR')
			line = event.target.parentNode;
		else if(event.target.parentNode.parentNode.tagName=='TR')
			line = event.target.parentNode.parentNode;
			
		if(event.target.className=='reserve')		ShowPopupWin('/docs.php?l=inf&mode=srv&opt=rezerv&pos='+line.lineIndex)
		else if(event.target.className=='offer')	ShowPopupWin('/docs.php?l=inf&mode=srv&opt=p_zak&pos='+line.lineIndex)
		else if(event.target.className=='transit')	ShowPopupWin('/docs.php?l=inf&mode=srv&opt=vputi&pos='+line.lineIndex);
		else {
			if(skladlist.needDialog)
			{
			var s="<table width='200px'><tr><td>Цена:</td><td><input type='text' id='pop_cost' value='"+event.target.parentNode.data.cost+"'></td></tr><tr><td>Количество:</td><td><input type='text' id='pop_cnt' value='1'></td></tr></table>"
			jDialog(s,'Укажите цену и количество',function()
				{
					var data=event.target.parentNode.data
					data.cost=document.getElementById('pop_cost').value
					AddToPosList(data, document.getElementById('pop_cnt').value)
				},'icon-confirm')
			var pop_cost=document.getElementById('pop_cost')
			pop_cost.focus()
			}
			else AddToPosList(line.data)
		}
	}

	function rcvDataSuccess(msg)
	{
		try
		{
			var json=eval('('+msg+')');
			if(json.response==0)
				jAlert(json.message,"Ошибка", {}, 'icon_err');
			else if(json.response=='sklad_list')
			{
				skladlist.innerHTML = '';
				for(var i=0;i<json.content.length;i++)
				{
					AddLine(json.content[i]);
				}
			}
			else if(json.response=='group_list') {
				rebuildGroupList(json.content);
			}
			else jAlert("Обработка полученного сообщения не реализована<br>"+msg, "Вставка строки в документ", null,  'icon_err');
		}
		catch(e)
		{
			jAlert("Критическая ошибка!<br>Если ошибка повторится, уведомите администратора о том, при каких обстоятельствах возникла ошибка!"+
			"<br><br><i>Информация об ошибке</i>:<br>"+e.name+": "+e.message+"<br>"+msg, "rcv Вставка строки в документ", null,  'icon_err');
		}
	}

	function AddToPosList(data, cnt) {
		if(!cnt)
			cnt=1;
		data.cnt = cnt;
		callback('sel_for_add', data);
	}
	
	init();
	return container;
}


function getSkladList(event, group)
{
	var skladlist=document.getElementById('sklad_list');
	return skladlist.getGroupData(event, group);
}
