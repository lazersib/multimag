function PosEditorInit(base_url, editable)
{
	var poslist=document.getElementById('poslist')
	var p_sum=document.getElementById('sum')
	//poslist.doc_id=doc
	poslist.base_url=base_url
	poslist.editable=editable
	poslist.show_column=new Array()
	var skladview=SkladViewInit(/*doc*/)
	PladdInit()
	
	if(!poslist.editable)
	{
		skladview.style.display='none'
	}
	
	poslist.refresh=function()
	{
		$.ajax({ 
			type:   'GET', 
			url:    base_url, 
			data:   'opt=jget', 
			success: function(msg) { poslist.tBodies[0].innerHTML=''; rcvDataSuccess(msg); }, 
			error:   function() { jAlert('Ошибка соединения!','Получение списка товаров',null,'icon_err'); }, 
		});
	}
	
	poslist.refresh()
	
	poslist.doInputKeyDown=function(e)
	{
		var e = e||window.event;
		if(e.keyCode==40)
		{
			var row=this.parentNode.parentNode.nextSibling
			if(row==null)		return false
			if(row.nodeType!=1)	return false
			var inputs=row.getElementsByTagName('input')
			for(var i=0;i<inputs.length;i++)
			{
				if(inputs[i].name==this.name)	inputs[i].focus()
			}
			return false
		}
		else if(e.keyCode==38)
		{
			var row=this.parentNode.parentNode.previousSibling
			if(row.nodeType!=1)	return false
			var inputs=row.getElementsByTagName('input')
			for(var i=0;i<inputs.length;i++)
			{
				if(inputs[i].name==this.name)	inputs[i].focus()
			}
			return false
		}
		else if(e.keyCode==37 && e.shiftKey==true)
		{
			var row=this.parentNode.parentNode
			if(row.nodeType!=1)	return false
			var inputs=row.getElementsByTagName('input')
			for(var i=0;i<inputs.length;i++)
			{
				if(this.name=='cnt' && inputs[i].name=='cost')	inputs[i].focus()
				else if(this.name=='sum' && inputs[i].name=='cnt')	inputs[i].focus()
			}
			return false
		}
		else if(e.keyCode==39 && e.shiftKey==true)
		{
			var row=this.parentNode.parentNode
			if(row.nodeType!=1)	return false
				var inputs=row.getElementsByTagName('input')
				for(var i=0;i<inputs.length;i++)
				{
					if(this.name=='cost' && inputs[i].name=='cnt')		inputs[i].focus()
					else if(this.name=='cnt' && inputs[i].name=='sum')	inputs[i].focus()
				}
				return false
		}
		//return false
	}
	
	poslist.doInputBlur=function()
	{
		if(this.old_value==this.value)	return
		var line=this.parentNode.parentNode
		line.className='el'
		$.ajax({ 
			type:   'GET', 
		       url:    base_url, 
		       data:   'opt=jup&type='+this.name+'&value='+this.value+'&line_id='+line.lineIndex, 
		       success: function(msg) { rcvDataSuccess(msg); }, 
		       error:   function() { jAlert('Ошибка соединения!','Обновление данных',function() {},'icon_err'); }, 
		});
	}
	
	poslist.AddLine=function(data)
	{
		var row_cnt=poslist.tBodies[0].rows.length
		var row=poslist.tBodies[0].insertRow(row_cnt)
		row.lineIndex=data.line_id
		row.id='posrow'+data.line_id
		var sum=(data.cost*data.cnt).toFixed(2)
		row.sklad_cnt=Number(data.sklad_cnt)
		row.ondblclick=row.oncontextmenu=function(){ ShowContextMenu(event ,'/docs.php?mode=srv&opt=menu&doc=0&pos='+data.pos_id); return false }
		var linehtml="<td>"+(row_cnt+1)
		if(poslist.editable)	linehtml+="<img src='/img/i_del.png' class='pointer' alt='Удалить' id='del"+row.lineIndex+"'>"
		linehtml+="</td>"
		if(poslist.show_column['vc']>0)	linehtml+="<td>"+data.vc+"</td>"
		linehtml+="<td class='la'>"+data.name+"</td><td>"+data.scost+"</td><td>"
		if(poslist.editable)	linehtml+="<input type='text' name='cost' value='"+data.cost+"'>"
		else			linehtml+=data.cost
		linehtml+="</td><td>"
		if(poslist.editable)	linehtml+="<input type='text' name='cnt' value='"+data.cnt+"'>"
		else			linehtml+=data.cnt
		linehtml+="</td><td>"
		if(poslist.editable)	linehtml+="<input type='text' name='sum' value='"+sum+"'>"
		else			linehtml+=sum
		linehtml+="</td><td>"+data.sklad_cnt+"</td><td>"+data.mesto+"</td>"
		if(poslist.show_column['sn']>0)	linehtml+="<td id='sn"+row.lineIndex+"'>"+data.sn+"</td>"
		row.innerHTML=linehtml
		
		if(poslist.editable)
		{
			if(Number(data.cnt)>Number(data.sklad_cnt))	row.style.color="#f00";
			var inputs=row.getElementsByTagName('input')
			for(var i=0;i<inputs.length;i++)
			{
				inputs[i].onkeydown=poslist.doInputKeyDown
				inputs[i].onblur=poslist.doInputBlur
				inputs[i].old_value=inputs[i].value
			}
			
			var img_del=document.getElementById('del'+data.line_id)
			img_del.onclick=poslist.doDeleteLine
			if(poslist.show_column['sn']>0)
			{
				var sn_cell=document.getElementById('sn'+data.line_id)
				sn_cell.onclick=poslist.showSnEditor
			}
		}
	}
	
	poslist.UpdateLine=function(data)
	{
		var line=document.getElementById('posrow'+data.line_id)
		var inputs=line.getElementsByTagName('input')
		for(var i=0;i<inputs.length;i++)
		{
			//alert(inputs[i].name)
			if(inputs[i].name=='cnt')	inputs[i].value=data.cnt
			else if(inputs[i].name=='cost')	inputs[i].value=Number(data.cost).toFixed(2)
			else if(inputs[i].name=='sum')	inputs[i].value=Number(data.cost*data.cnt).toFixed(2)
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
		$('#'+line.id).addClass('dl')
		$.ajax({ 
			type:   'GET', 
			url:    base_url, 
			data:   'opt=jdel&line_id='+line.lineIndex, 
			success: function(msg) { rcvDataSuccess(msg); }, 
			error:   function() { jAlert('Ошибка соединения!','Получение списка товаров',null,'icon_err'); }, 
		});
	}
	
	function rcvDataSuccess(msg)
	{
		try
		{
			var json=eval('('+msg+')');
			if(json.response==0)
				jAlert(json.message,"Ошибка", {}, 'icon_err');
			else if(json.response==2)
			{
				for(var i=0;i<json.content.length;i++)
				{
					poslist.AddLine(json.content[i])
				}
				p_sum.innerHTML='Итого: <b>'+(poslist.tBodies[0].rows.length)+'</b> поз. на сумму <b>'+json.sum+'</b> руб.'
			}
			else if(json.response==4)
			{
				poslist.UpdateLine(json.update)
				p_sum.innerHTML='Итого: <b>'+(poslist.tBodies[0].rows.length)+'</b> поз. на сумму <b>'+json.sum+'</b> руб.'
			}
			else if(json.response==5)
			{
				poslist.RemoveLine(json.remove.line_id)
				p_sum.innerHTML='Итого: <b>'+(poslist.tBodies[0].rows.length)+'</b> поз. на сумму <b>'+json.sum+'</b> руб.'
			}
			else jAlert("Обработка полученного сообщения не реализована<br>"+msg, "Изменение списка товаров", null,  'icon_err');
		}
		catch(e)
		{
			jAlert("Критическая ошибка!<br>Если ошибка повторится, уведомите администратора о том, при каких обстоятельствах возникла ошибка!"+
			"<br><br><i>Информация об ошибке</i>:<br>"+e.name+": "+e.message+"<br>"+msg, "Вставка строки в документ", null,  'icon_err');
		}	
	}
	
	// Редактор серийных номеров
	poslist.showSnEditor=function (event)
	{
		var poslist_line=event.target.parentNode
		var line=poslist_line.lineIndex
		var sn_cnt=0
		$.ajax({ 
			type:   'GET', 
			url:    base_url, 
			data:   'opt=jsn&a=l&line='+line,
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
				
				// ??????????????????????????????????????????????????????????????????????????????????????????
				$("#sn").autocomplete("/doc.php", {
					delay:300,
					minChars:1,
					matchSubset:1,
					autoFill:false,
					selectFirst:true,
					matchContains:1,
					cacheLength:10,
					maxItemsToShow:15, 
					extraParams:{'mode':'srv','opt':'snp', 'doc': '1', 'pos': line}
				});
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
				url:    base_url, 
				data:   'opt=jsn&a=d&line='+line,
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
				url:    base_url, 
				data:   'opt=sns&pos='+line+'&sn='+sn.value, 
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
					var row=document.createElement('tr')
					row.id='snl'+json.sn_id
					row.innerHTML="<td><img src='/img/i_del.png'  id='sndel|"+json.sn_id+"'></td><td>"+json.sn+"</td>"
					sn_list.appendChild(row)
					var img_del=document.getElementById('sndel|'+json.sn_id)
					img_del.onclick=SnDel
					sn_cnt++;
				}	
			}
			catch(e)
			{
				jAlert("Критическая ошибка!<br>Если ошибка повторится, уведомите администратора о том, при каких обстоятельствах возникла ошибка!"+
				"<br><br><i>Информация об ошибке</i>:<br>"+e.name+": "+e.message, "Добавление серийного номера",function() {},  'icon_err');
			}
		}
	}

	
	return poslist
}

// Строка быстрого добавления наименований
function PladdInit()
{
	var poslist=document.getElementById('poslist');
	var pladd=document.getElementById('pladd');
	if(!poslist.editable)
	{
		pladd.style.display='none'
	}
	
	//pladd.style.backgroundColor='#000';	
	var pos_id=document.getElementById('pos_id');
	var pos_vc=document.getElementById('pos_vc');
	var pos_name=document.getElementById('pos_name');
	var pos_scost=document.getElementById('pos_scost');
	var pos_cost=document.getElementById('pos_cost');
	var pos_cnt=document.getElementById('pos_cnt');
	var pos_sum=document.getElementById('pos_sum');
	var pos_sklad_cnt=document.getElementById('pos_sklad_cnt');
	var pos_mesto=document.getElementById('pos_cnt');
	
	var p_sum=document.getElementById('sum');
	
	$("#pos_name").autocomplete("/docs.php", {
		delay:300,
		minChars:1,
		matchSubset:1,
		autoFill:false,
		selectFirst:false,
		matchContains:1,
		cacheLength:10,
		maxItemsToShow:20, 
		formatItem:nameFormat,
		onItemSelect:nameselectItem,
		extraParams:{'l':'sklad','mode':'srv','opt':'ac'}
	});
	
	if(pos_vc)
	{
		$("#pos_vc").autocomplete("/docs.php", {
			delay:300,
			minChars:1,
			matchSubset:1,
			autoFill:false,
			selectFirst:false,
			matchContains:1,
			cacheLength:10,
			maxItemsToShow:20, 
			formatItem:vcFormat,
			onItemSelect:vcselectItem,
			extraParams:{'l':'sklad','mode':'srv','opt':'acv'}
		});
	}
	function nameFormat (row, i, num) {
		var result = row[0] + "<em class='qnt'>произв. " +
		row[2] + ", код: "+ row[3] + "</em> ";
		return result;
	}
	
	function nameselectItem(li) {
		if( li == null ) var sValue = "Ничего не выбрано!";
		else if( !!li.extra ) var sValue = li.extra[0];
		//else var sValue = li.selectValue;
		pos_id.value=sValue;
		pos_vc.value=li.extra[2];
		pos_cost.value=0.5;
		pos_cnt.value=1;
		pos_name.focus();
		
		pladd.doRefresh()		
	}
	
	function vcFormat (row, i, num)
	{
		var result = row[0];
		return result;
	}
	
	function vcselectItem(li)
	{
		
		if( li == null ) var sValue = "Ничего не выбрано!";
		else if( !!li.extra ) var sValue = li.extra[0];
		//else var sValue = li.selectValue;
		pos_id.value=sValue;
		pos_name.value=li.extra[2];
		pos_cost.value=0.5;
		pos_cnt.value=1;
		pos_vc.focus();
		
		pladd.doRefresh()
	}
	
	function AddData()
	{
		$.ajax({ 
			type:   'GET', 
			url:    poslist.base_url, 
			data:   'opt=jadd&pos='+pos_id.value+'&cnt='+pos_cnt.value+'&cost='+pos_cost.value, 
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
			else if(json.response==1)	// Вставка строки
			{
				poslist.AddLine(json.add)
				
				p_sum.innerHTML='Итого: <b>'+'</b> поз. на сумму <b>'+json.sum+'</b> руб.'
				pladd.Reset()
			}
			else if(json.response==4)
			{
				poslist.UpdateLine(json.update)
				pladd.Reset()
			}
			else jAlert("Обработка полученного сообщения не реализована<br>"+msg, "Вставка строки в документ", null,  'icon_err');
		}
		catch(e)
		{
			jAlert("Критическая ошибка!<br>Если ошибка повторится, уведомите администратора о том, при каких обстоятельствах возникла ошибка!"+
			"<br><br><i>Информация об ошибке</i>:<br>"+e.name+": "+e.message+"<br>"+msg, "Вставка строки в документ", null,  'icon_err');
		}
	}
	
	pladd.Reset=function()
	{
		pos_id.value=''
		if(pos_vc)	pos_vc.value=''
		pos_name.value=''
		pos_scost.innerHTML=''
		pos_cost.value=''
		pos_cnt.value=''
		pos_sum.innerHTML=''
		pos_sklad_cnt.innerHTML=''
		pos_mesto.innerHTML=''
		pos_id.focus();
		$('#pladd').removeClass('process')
		$('#pladd').removeClass('error')
	}
	
	pladd.doRefresh=function()
	{
		if(parseInt(pos_id.value)==0  || parseInt(pos_id.value).toString()=='NaN')	return
		$('#pladd').addClass('process')
		$('#pladd').removeClass('error')
		$.ajax({ 
			type:   'GET', 
			url:    poslist.base_url, 
			data:   'opt=jgpi&pos='+parseInt(pos_id.value), 
			success: function(msg) { pladd.doRefreshSuccess(msg); }, 
			error:   function() { jAlert('Ошибка соединения!','Автодополнение по коду',null,'icon_err'); $('#pladd').removeClass('process'); }, 
		});
		
	}
	
	pladd.doRefreshSuccess=function(msg)
	{
		try
		{
			var json=eval('('+msg+')');
			if(json.response==0)
				jAlert(json.message,"Ошибка", {}, 'icon_err');
			else if(json.response==3)	// Вставка строки
			{
				pladd.Refresh(json.data)
			}
			else jAlert("Обработка полученного сообщения не реализована<br>"+msg, "Получение информации о позиции", null,  'icon_err');
		}
		catch(e)
		{
			jAlert("Критическая ошибка!<br>Если ошибка повторится, уведомите администратора о том, при каких обстоятельствах возникла ошибка!"+
			"<br><br><i>Информация об ошибке</i>:<br>"+e.name+": "+e.message+"<br>"+msg, "Автодополнение", null,  'icon_err');
		}
		$('#pladd').removeClass('process');
	}
	
	pladd.Refresh=function(data)
	{
		if(pos_vc)	pos_vc.value=data.vc
		pos_name.value=data.name
		pos_scost.innerHTML=data.scost
		pos_cost.value=data.cost
		pos_cnt.value=data.cnt
		pos_sum.innerHTML=data.cost*data.cnt
		pos_sklad_cnt.innerHTML=data.sklad_cnt
		pos_mesto.innerHTML=data.mesto
		if(data.line_id>0)	$('#pladd').addClass('error')
	}
	
	function KeyUp(e)
	{
		var e = e||window.event;
		if(e.keyCode==13)	AddData();
		if( this.id=='pos_cost' || this.id=='pos_cnt' )
		{
			pos_sum.innerHTML=parseFloat(pos_cost.value)*parseFloat(pos_cnt.value)
		}
		if( this.id=='pos_id')
		{
			if(parseInt(pos_id.value)!=pos_id.old_value )
			{
				pos_id.old_value=parseInt(pos_id.value)
				pladd.doRefresh()			
			}
		}
	}
	
	pos_id.old_value=0
	
	pos_id.onkeyup=KeyUp
	//pos_vc.onkeydown=KeyDown
	//pos_name.onkeydown=KeyDown
	pos_cost.onkeyup=KeyUp
	pos_cnt.onkeyup=KeyUp
	pladd.Reset()
}

// Блок со списком складской номенклатуры
function SkladViewInit(doc)
{
	var poslist=document.getElementById('poslist')
	var skladview=document.getElementById('sklad_view')
	var skladlist=document.getElementById('sklad_list')
	var p_sum=document.getElementById('sum')
	var sklsearch=document.getElementById('sklsearch')
	var groupdata_cache=new Array()
	var old_hl=0
	skladview.show_column=new Array();
	sklsearch.timer=0
	skladlist.needDialog=0
	
	sklsearch.onkeydown=function(event)
	{
		if(sklsearch.timer)	window.clearTimeout(sklsearch.timer)
		sklsearch.timer=window.setTimeout(function(){skladlist.getSearchResult(event)}, 1000)	
	}
	
	skladlist.getGroupData=function (event,group)
	{
		if(old_hl)	old_hl.style.backgroundColor=''
		event.target.parentNode.style.backgroundColor='#ffb'
		old_hl=event.target.parentNode
		skladlist.innerHTML="<tr><td colspan='20' style='text-align: center;'><img src='/img/icon_load.gif' alt='Загрузка...'></td></tr>"
		if(groupdata_cache[group])	rcvDataSuccess(groupdata_cache[group])
		$.ajax({ 
			type:   'GET', 
			url:    poslist.base_url, 
			data:   'opt=jsklad&group_id='+group, 
			success: function(msg) { groupdata_cache[group]=msg;rcvDataSuccess(msg); }, 
			error:   function() { jAlert('Ошибка соединения!','Получение содержимого группы',null,'icon_err'); }, 
		});
		return false
	}
	
	skladlist.getSearchResult=function (event)
	{
		if(old_hl)	old_hl.style.backgroundColor=''
		old_hl=0
		s_str=event.target.value
		if(s_str=='')	return
		skladlist.innerHTML="<tr><td colspan='20' style='text-align: center;'><img src='/img/icon_load.gif' alt='Загрузка...'></td></tr>"
		$.ajax({ 
			type:   'GET', 
			url:    poslist.base_url, 
			data:   'opt=jsklads&s='+encodeURIComponent(s_str), 
			success: function(msg) { rcvDataSuccess(msg); }, 
			error:   function() { jAlert('Ошибка соединения!','Получение содержимого группы',null,'icon_err'); }, 
		});
		return false
	}
	
	skladlist.AddLine=function(data)
	{
		var row_cnt=skladlist.rows.length
		var row=skladlist.insertRow(row_cnt)
		var linehtml=''
		if(data.id!='header')
		{
			row.lineIndex=data.id
			row.id='skladrow'+data.id
			row.data=data
			row.className='pointer'
			//row.onclick=function() {AddData(data)}
			if(poslist.editable)	row.onclick=skladlist.clickRow
			row.oncontextmenu=function(){ ShowContextMenu(event ,'/docs.php?mode=srv&opt=menu&doc=0&pos='+data.id); return false }
			linehtml+="<td>"+data.id+"</td>"
			if(skladview.show_column['vc']>0)	linehtml+="<td>"+data.vc+"</td>"
			linehtml+="<td class='la'>"+data.name+"</td><td class='la'>"+data.vendor+"</td><td class='"+data.cost_class+"'>"+data.cost+"</td><td>"+data.liquidity+"</td><td>"+data.rcost+"</td><td>"+data.analog+"</td>"
			if(skladview.show_column['tdb']>0)	linehtml+="<td>"+data.type+"</td><td>"+data.d_int+"</td><td>"+data.d_ext+"</td><td>"+data.size+"</td><td>"+data.mass+"</td>"
			if(skladview.show_column['rto']>0)	linehtml+="<td class='reserve'>"+data.reserve+"</td><td class='offer'>"+data.offer+"</td><td class='transit'>"+data.transit+"</td>"
			linehtml+="<td>"+data.cnt+"</td><td>"+data.allcnt+"</td><td>"+data.place+"</td>"		
		}
		else
		{
			var count=10;
			if(skladview.show_column['vc']>0)	count++;
			if(skladview.show_column['tdb']>0)	count+=5;
			if(skladview.show_column['rto']>0)	count+=3;
			linehtml+="<th colspan='"+count+"'>"+data.name+"</th>"
		}
		row.innerHTML=linehtml
// 		var img_del=document.getElementById('del'+data.line_id)
// 		img_del.onclick=poslist.doDeleteLine
	}
	
	skladlist.clickRow=function(event)
	{
		if(event.target.className=='reserve')		OpenW('/docs.php?l=inf&mode=srv&opt=rezerv&pos='+this.data.id)
		else if(event.target.className=='offer')	ShowPopupWin('/docs.php?l=inf&mode=srv&opt=p_zak&pos='+this.data.id)
		else if(event.target.className=='transit')	ShowPopupWin('/docs.php?l=inf&mode=srv&opt=vputi&pos='+this.data.id);
		else
		{
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
			else AddToPosList(this.data)
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
				skladlist.innerHTML=''
				for(var i=0;i<json.content.length;i++)
				{
					skladlist.AddLine(json.content[i])
				}
			}
			else if(json.response==1)	// Вставка строки
			{
				poslist.AddLine(json.add)				
				p_sum.innerHTML='Итого: <b>'+'</b> поз. на сумму <b>'+json.sum+'</b> руб.'
			}
			else if(json.response==4)
			{
				poslist.UpdateLine(json.update)
			}
			else jAlert("Обработка полученного сообщения не реализована<br>"+msg, "Вставка строки в документ", null,  'icon_err');
		}
		catch(e)
		{
			jAlert("Критическая ошибка!<br>Если ошибка повторится, уведомите администратора о том, при каких обстоятельствах возникла ошибка!"+
			"<br><br><i>Информация об ошибке</i>:<br>"+e.name+": "+e.message+"<br>"+msg, "Вставка строки в документ", null,  'icon_err');
		}	
	}
	
	function AddToPosList(data, cnt)
	{
		if(!cnt)	cnt=1
		$.ajax({ 
			type:   'GET', 
			url:    poslist.base_url, 
			data:   'opt=jadd&pos='+data.id+'&cost='+data.cost+'&cnt='+cnt, 
			success: function(msg) { rcvDataSuccess(msg); }, 
		        error:   function() { jAlert('Ошибка соединения!','Добавление наименования',null,'icon_err'); }, 
		});
	}
	return skladview
}


function getSkladList(event, group)
{
	var skladlist=document.getElementById('sklad_list');
	return skladlist.getGroupData(event, group)	
}
