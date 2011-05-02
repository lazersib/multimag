function PosEditorInit(doc)
{
	var poslist=document.getElementById('poslist')
	var p_sum=document.getElementById('sum')
	poslist.doc_id=doc
	SkladViewInit(doc)
	
	$.ajax({ 
		type:   'POST', 
	       url:    '/doc.php', 
	       data:   'doc='+poslist.doc_id+'&mode=srv&opt=jget', 
	       success: function(msg) { poslist.tBodies[0].innerHTML=''; rcvDataSuccess(msg); }, 
	       error:   function() { jAlert('Ошибка соединения!','Получение списка товаров',null,'icon_err'); }, 
	});
	
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
			type:   'POST', 
		       url:    '/doc.php', 
		       data:   'doc='+poslist.doc_id+'&mode=srv&opt=jup&type='+this.name+'&value='+this.value+'&line_id='+line.lineIndex, 
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
		row.oncontextmenu=function(){ ShowContextMenu(event ,'/docs.php?mode=srv&opt=menu&doc=0&pos='+data.pos_id); return false }
		row.innerHTML="<td>"+(row_cnt+1)+"<img src='/img/i_del.png' alt='Удалить' id='del"+row.lineIndex+"'></td><td>"+data.vc+"</td><td class='la'>"+data.name+"</td><td>"+data.scost+"</td><td><input type='text' name='cost' value='"+data.cost+"'></td><td><input type='text' name='cnt' value='"+data.cnt+"'></td><td><input type='text' name='sum' value='"+sum+"'></td><td>"+data.sklad_cnt+"</td><td>"+data.mesto+"</td>"
		
		var inputs=row.getElementsByTagName('input')
		for(var i=0;i<inputs.length;i++)
		{
			//alert(inputs[i].name)
			inputs[i].onkeydown=poslist.doInputKeyDown
			inputs[i].onblur=poslist.doInputBlur
			inputs[i].old_value=inputs[i].value
		}
		
		var img_del=document.getElementById('del'+data.line_id)
		img_del.onclick=poslist.doDeleteLine
	}
	
	poslist.UpdateLine=function(data)
	{
		//alert('posrow'+data.line_id)
		var line=document.getElementById('posrow'+data.line_id)
		var inputs=line.getElementsByTagName('input')
		for(var i=0;i<inputs.length;i++)
		{
			//alert(inputs[i].name)
			if(inputs[i].name=='cnt')	inputs[i].value=data.cnt
			else if(inputs[i].name=='cost')	inputs[i].value=Number(data.cost).toFixed(2)
			else if(inputs[i].name=='sum')	inputs[i].value=(data.cost*data.cnt).toFixed(2)
			inputs[i].old_value=inputs[i].value
		}
		line.className='hl'		
		window.setTimeout(function(){line.className='';}, 2000)
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
			type:   'POST', 
		       url:    '/doc.php', 
		       data:   'doc='+poslist.doc_id+'&mode=srv&opt=jdel&line_id='+line.lineIndex, 
		       success: function(msg) { rcvDataSuccess(msg); }, 
		       error:   function() { jAlert('Ошибка соединения!','Получение списка товаров',null,'icon_err'); }, 
		});
		
		//window.setTimeout(function(){line.parentNode.removeChild(line)}, 2000)
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
				//p_sum.innerHTML='Итого: <b>'+(i)+'</b> поз. на сумму <b>'+json.sum+'</b> руб.'
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
}

// Строка быстрого добавления наименований
function PladdInit()
{
	var poslist=document.getElementById('poslist');
	var pladd=document.getElementById('pladd');

	
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
			type:   'POST', 
			url:    '/doc.php', 
			data:   'doc='+poslist.doc_id+'&mode=srv&opt=jadd&pos='+pos_id.value+'&cnt='+pos_cnt.value+'&cost='+pos_cost.value, 
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
		pos_vc.value=''
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
			type:   'POST', 
		       url:    '/doc.php', 
		       data:   'doc='+poslist.doc_id+'&mode=srv&opt=jgpi&pos='+parseInt(pos_id.value), 
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
		pos_vc.value=data.vc
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
	var poslist=document.getElementById('poslist');
	var skladview=document.getElementById('sklad_view');
	var skladlist=document.getElementById('sklad_list');
	var p_sum=document.getElementById('sum')
	var groupdata_cache=new Array()
	
	skladlist.getGroupData=function (group)
	{
		skladlist.innerHTML="<tr><td colspan='20' style='text-align: center;'><img src='/img/icon_load.gif' alt='Загрузка...'></td></tr>"
		if(groupdata_cache[group])	rcvDataSuccess(groupdata_cache[group])
		$.ajax({ 
			type:   'POST', 
		       url:    '/doc.php', 
		       data:   'doc='+poslist.doc_id+'&mode=srv&opt=jsklad&group_id='+group, 
		       success: function(msg) { groupdata_cache[group]=msg;rcvDataSuccess(msg); }, 
		       error:   function() { jAlert('Ошибка соединения!','Получение содержимого группы',null,'icon_err'); }, 
		});
		return false
	}
	
	skladlist.AddLine=function(data)
	{
		var row_cnt=skladlist.rows.length
		var row=skladlist.insertRow(row_cnt)
		row.lineIndex=data.id
		row.id='skladrow'+data.id
		row.data=data
		row.className='pointer'
		//row.onclick=function() {AddData(data)}
		row.onclick=skladlist.clickRow
		row.oncontextmenu=function(){ ShowContextMenu(event ,'/docs.php?mode=srv&opt=menu&doc=0&pos='+data.id); return false }
		row.innerHTML="<td>"+data.id+"</td><td>"+data.vc+"</td><td class='la'>"+data.name+"</td><td class='la'>"+data.vendor+"</td><td>"+data.cost+"</td><td>"+data.liquidity+"</td><td>"+data.rcost+"</td><td>"+data.analog+"</td><td>"+data.type+"</td><td>"+data.d_int+"</td><td>"+data.d_ext+"</td><td>"+data.size+"</td><td>"+data.mass+"</td><td class='reserve'>"+data.reserve+"</td><td class='offer'>"+data.offer+"</td><td class='transit'>"+data.transit+"</td><td>"+data.cnt+"</td><td>"+data.allcnt+"</td><td>"+data.place+"</td>"
		
// 		var inputs=row.getElementsByTagName('input')
// 		for(var i=0;i<inputs.length;i++)
// 		{
// 			//alert(inputs[i].name)
// 			inputs[i].onkeydown=poslist.doInputKeyDown
// 			inputs[i].onblur=poslist.doInputBlur
// 			inputs[i].old_value=inputs[i].value
// 		}
// 		
// 		var img_del=document.getElementById('del'+data.line_id)
// 		img_del.onclick=poslist.doDeleteLine
	}
	
	skladlist.clickRow=function(event)
	{
		if(event.target.className=='reserve')		OpenW('/docs.php?l=inf&mode=srv&opt=rezerv&pos='+this.data.id)
		else if(event.target.className=='offer')	ShowPopupWin('/docs.php?l=inf&mode=srv&opt=p_zak&pos='+this.data.id)
		else if(event.target.className=='transit')	ShowPopupWin('/docs.php?l=inf&mode=srv&opt=vputi&pos='+this.data.id);
		else						AddToPosList(this.data)

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
	
	function AddToPosList(data)
	{
		$.ajax({ 
			type:   'POST', 
			url:    '/doc.php', 
			data:   'doc='+poslist.doc_id+'&mode=srv&opt=jadd&pos='+data.id+'&cnt=1&cost='+data.cost, 
			success: function(msg) { rcvDataSuccess(msg); }, 
		        error:   function() { jAlert('Ошибка соединения!','Добавление наименования',null,'icon_err'); }, 
		});
	}
}

function getSkladList(group)
{
	var skladlist=document.getElementById('sklad_list');
	return skladlist.getGroupData(group)	
}

$(document).ready(function(){
	PladdInit();	
	
});