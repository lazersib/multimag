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

	function validateINN(input,no_empty)
	{
		function test_valid()
		{
			var v=input.value
			if(v.length==0)
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

			if(v.length==10)
			{
				var c=((2*v[0]+4*v[1]+10*v[2]+3*v[3]+5*v[4]+9*v[5]+4*v[6]+6*v[7]+8*v[8])%11)%10
				if(c!=Number(v[9]))
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
			else if(v.length==12)
			{
				var c11=((7*v[0]+2*v[1]+4*v[2]+10*v[3]+3*v[4]+5*v[5]+9*v[6]+4*v[7]+6*v[8]+8*v[9])%11)%10
				var c12=((3*v[0]+7*v[1]+2*v[2]+4*v[3]+10*v[4]+3*v[5]+5*v[6]+9*v[7]+4*v[8]+6*v[9]+8*v[10])%11)%10
				if(c11!=Number(v[10]) || c12!=Number(v[11]))
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
			input.style.color="#f00"
			input.valid=false
			buttons_toggle()
			return true
		}
		input.addEventListener( 'keyup', test_valid, false)
		test_valid()
	}

	function validateBikRs(input_bik,input_rs)
	{
		function test_valid()
		{
			if(input_bik.value.length==0 && input_rs.value.length==0)
			{
				input.style.color=""
				input.valid=true
				buttons_toggle()
				return true
			}

			if(input_rs.value.length!=20)
			{
				input_rs.style.color="#f00"
				input_rs.valid=false
				buttons_toggle()
				return true
			}

			if(input_bik.value.length!=9)
			{
				input_bik.style.color="#f00"
				input_bik.valid=false
				buttons_toggle()
				return true
			}

			var sum=0
			var coef=[7,1,3]
			for(var i=0;i<input.value.length;i++)
			{
				sum+=Number(input.value[i])*coef[i%3]
			}
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
			input_rs.style.color="#f00"
			input_bik.style.color="#f00"
			input_rs.valid=false
			input_bik.valid=false
			buttons_toggle()
			return true
		}
		input_rs.addEventListener( 'keyup', test_valid, false)
		input_bik.addEventListener( 'keyup', test_valid, false)
		test_valid()
	}

	var input_bik=0,input_rs=0
	for(var i=0; i<form_inputs.length; i++) {
		if(hasClass(form_inputs[i],'validate'))	{
			if(hasClass(form_inputs[i],'phone'))
				validatePhone(form_inputs[i], hasClass(form_inputs[i],'no_empty'))
			else if(hasClass(form_inputs[i],'email'))
				validateEmail(form_inputs[i], hasClass(form_inputs[i],'no_empty'))
			else if(hasClass(form_inputs[i],'bik'))
				input_bik=form_inputs[i]
			else if(hasClass(form_inputs[i],'rs'))
				input_rs=form_inputs[i]
			//else if(hasClass(form_inputs[i],'inn'))
			//	validateINN(form_inputs[i], hasClass(form_inputs[i],'no_empty'))
// 			else if(hasClass(form_inputs[i],'rs'))
// 				validateRS(form_inputs[i], hasClass(form_inputs[i],'no_empty'))
		}
	}
	if(input_bik && input_rs)
		validateBikRs(input_bik,input_rs)
	return validator
}
