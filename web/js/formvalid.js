//	MultiMag v0.1 - Complex sales system
//
//	Copyright (C) 2005-2012, BlackLight, TND Team, http://tndproject.org
//
//	This program is free software: you can redistribute it and/or modify
//	it under the terms of the GNU Affero General Public License as
//	published by the Free Software Foundation, either version 3 of the
//	License, or (at your option) any later version.
//
//	This program is distributed in the hope that it will be useful,
//	but WITHOUT ANY WARRANTY; without even the implied warranty of
//	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//	GNU Affero General Public License for more details.
//
//	You should have received a copy of the GNU Affero General Public License
//	along with this program.  If not, see <http://www.gnu.org/licenses/>.
//

// Валидаторы форм
// Подсвечивают поле красным и блокируют отправку формы, если данные неверны

function form_validator(form_id)
{
	var validator = new Object()
	//validator.form_but=document.getElementById(form_button_id)
	var form=document.getElementById(form_id)
	var form_buttons=form.getElementsByTagName('button')
	var form_inputs=form.getElementsByTagName('input')
	
	function buttons_disable(dis) {
		for(var i=0; i<form_buttons.length; i++) {
			if(form_buttons[i].type='submit')
				form_buttons[i].disabled=dis
		}
	}
	
	function buttons_toggle()
	{
		var valid=true
		for(var i=0; i<form_inputs.length; i++) {
			if(hasClass(form_inputs[i],'validate'))
			{
				if(!form_inputs[i].valid)	valid=false
			}
		}
		buttons_disable(!valid)
	}
	
	
	function hasClass(elem, className) {
		    return new RegExp("(^|\\s)"+className+"(\\s|$)").test(elem.className)
	}
	
	function validatePhone(input,no_empty)
	{
		function test_valid()
		{
			if(input.value.length==0)
			{
				if(no_empty)
				{
					input.style.color="#f00"
					input.valid=false
					buttons_toggle()
				}
				else
				{
					input.style.color=""
					input.valid=true
					buttons_toggle()
				}
				return true
			}	
			var regexp=/^\+\d{8,15}$/
			if(!regexp.test(input.value))
			{
				input.style.color="#f00"
				input.valid=false
				buttons_toggle()
			}
			else
			{
				input.style.color=""
				input.valid=true
				buttons_toggle()
			}
			return true
		}
		input.addEventListener( 'keyup', test_valid, false)
		test_valid()
	}
	
	function validateEmail(input,no_empty)
	{
		function test_valid()
		{
			if(input.value.length==0)
			{
				if(no_empty)
				{
					input.style.color="#f00"
					input.valid=false
					buttons_toggle()
				}
				else
				{
					input.style.color=""
					input.valid=true
					buttons_toggle()
				}
				return true
			}	
			var regexp=/^\w+([-\.\w]+)*\w@\w(([-\.\w])*\w+)*\.\w{2,8}$/
			if(!regexp.test(input.value))
			{
				input.style.color="#f00"
				input.valid=false
				buttons_toggle()
			}
			else
			{
				input.style.color=""
				input.valid=true
				buttons_toggle()
			}
			return true
		}
		input.addEventListener( 'keyup', test_valid, false)
		test_valid()
	}
	
	function validateRS(input,no_empty)
	{
		function test_valid()
		{
			if(input.value.length==0)
			{
				if(no_empty)
				{
					input.style.color="#f00"
					input.valid=false
					buttons_toggle()
				}
				else
				{
					input.style.color=""
					input.valid=true
					buttons_toggle()
				}
				return true
			}
			
			if(input.value.length!=20)
			{
				input.style.color="#f00"
				input.valid=false
				buttons_toggle()
				return true
			}
			var sum=0
			var coef=[7,1,3]
			for(var i=0;i<input.value.length;i++)
			{
				sum+=Number(input.value[i])*coef[i%3]
			}
			if(sum%10==0)
			{
				input.style.color=""
				input.valid=true
				buttons_toggle()
				return true
			}
			alert(sum%10)
			input.style.color="#f00"
			input.valid=false
			buttons_toggle()
			return true
		}
		input.addEventListener( 'keyup', test_valid, false)
		test_valid()
	}
	
	
	for(var i=0; i<form_inputs.length; i++) {
		if(hasClass(form_inputs[i],'validate'))
		{
			if(hasClass(form_inputs[i],'phone'))
				validatePhone(form_inputs[i], hasClass(form_inputs[i],'no_empty'))
			else if(hasClass(form_inputs[i],'email'))
				validateEmail(form_inputs[i], hasClass(form_inputs[i],'no_empty'))
// 			else if(hasClass(form_inputs[i],'rs'))
// 				validateRS(form_inputs[i], hasClass(form_inputs[i],'no_empty'))
		}
	}
	
	return validator
}
