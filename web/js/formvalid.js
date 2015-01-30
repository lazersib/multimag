//	MultiMag v0.2 - Complex sales system
//
//	Copyright (C) 2005-2014, BlackLight, TND Team, http://tndproject.org
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
        var enabled = true;
        
        validator.enable = function (val) {
            enabled = val;
            if(!enabled) {
                for(var i=0; i<form_inputs.length; i++) {
                    if(hasClass(form_inputs[i],'validate'))	{
			hlErrorField(form_inputs[i],false);
                    }
                }
                buttons_disable(false);
            }
            else {
                for(var i=0; i<form_inputs.length; i++) {
                    if(hasClass(form_inputs[i],'validate'))	{
                        if(typeof(form_inputs[i].test_valid)=='function') {
                            form_inputs[i].test_valid();
                        }
                    }
                }
            }
        }

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
				if(!form_inputs[i].valid)
				{
					//alert(form_inputs[i].name)
					valid=false
				}
			}
		}
		buttons_disable(!valid)
	}


	function hasClass(elem, className) {
		    return new RegExp("(^|\\s)"+className+"(\\s|$)").test(elem.className)
	}

	function hlErrorField(field,hl)
	{
		if(hl)
		{
			field.style.borderColor="#f00"
			field.style.color="#f00"
			field.valid=false
		}
		else
		{
			field.style.borderColor=""
			field.style.color=""
			field.valid=true
		}
	}

	function validatePhone(input,no_empty)
	{
		function test_valid()
		{
                    if(!enabled) {
                        return true;
                    }
			if(input.value.length==0)
			{
				if(no_empty)
				{
					hlErrorField(input,true)
					buttons_toggle()
				}
				else
				{
					hlErrorField(input,false)
					buttons_toggle()
				}
				return true
			}
			var regexp=/^\+\d{8,15}$/
			if(!regexp.test(input.value))
			{
				hlErrorField(input,true)
				buttons_toggle()
			}
			else
			{
				hlErrorField(input,false)
				buttons_toggle()
			}
			return true
		}
		hlErrorField(input,false)
		input.addEventListener( 'keyup', test_valid, false);
                input.test_valid = test_valid;
		test_valid();
	}

	function validateEmail(input,no_empty)
	{
		function test_valid()
		{
                    if(!enabled) {
                        return true;
                    }
			if(input.value.length==0)
			{
				if(no_empty)
				{
					hlErrorField(input,true)
					buttons_toggle()
				}
				else
				{
					hlErrorField(input,false)
					buttons_toggle()
				}
				return true
			}
			var regexp=/^\w+([-\.\w]+)*\w@\w(([-\.\w])*\w+)*\.\w{2,8}$/
			if(!regexp.test(input.value))
			{
				hlErrorField(input,true)
				buttons_toggle()
			}
			else
			{
				hlErrorField(input,false)
				buttons_toggle()
			}
			return true
		}
		hlErrorField(input,false)
		input.addEventListener( 'keyup', test_valid, false);
                input.test_valid = test_valid;
		test_valid()
	}

	function validateINN(input,no_empty)
	{
		function test_valid()
		{
                    if(!enabled) {
                        return true;
                    }
			var a=input.value.split('/')
			var v=a[0]
			if(a.length>1)
			{
				if(a[1].length!=0 && a[1].length!=9)
				{
					hlErrorField(input,true)
					buttons_toggle()
					return true
				}
			}

			if(v.length==0)
			{
				if(no_empty)
				{
					hlErrorField(input,true)
					buttons_toggle()
				}
				else
				{
					hlErrorField(input,false)
					buttons_toggle()
				}
				return true
			}

			if(v.length==10)
			{
				var c=((2*v[0]+4*v[1]+10*v[2]+3*v[3]+5*v[4]+9*v[5]+4*v[6]+6*v[7]+8*v[8])%11)%10
				if(c!=Number(v[9]))
				{
					hlErrorField(input,true)
					buttons_toggle()
				}
				else
				{
					hlErrorField(input,false)
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
					hlErrorField(input,true)
					buttons_toggle()
				}
				else
				{
					hlErrorField(input,false)
					buttons_toggle()
				}
				return true
			}
			hlErrorField(input,true)
			buttons_toggle()
			return true
		}
		hlErrorField(input,false)
		input.addEventListener( 'keyup', test_valid, false);
                input.test_valid = test_valid;
		test_valid()
	}

	function validateBikRs(input_bik,input_rs,input_ks)
	{

		function test_str_schet(str)
		{
			var coef=[7,1,3]
			var sum=0
			for(var i=0;i<str.length;i++)
			{
				sum+=Number(str[i])*coef[i%3]
			}
			return sum%10;
		}

		function test_valid()
		{
                    if(!enabled) {
                        return true;
                    }
			if(input_bik.value.length==0 && input_rs.value.length==0 && input_ks.value.length==0 )
			{
				hlErrorField(input_bik,false)
				hlErrorField(input_rs,false)
				hlErrorField(input_ks,false)
				buttons_toggle()
				return true
			}
			var lf=0;
			if(input_rs.value.length!=20)
			{
				hlErrorField(input_rs,true)
				lf=1;
			}

			if(input_bik.value.length!=9)
			{
				hlErrorField(input_bik,true)
				lf=1
			}

			if(input_ks.value.length!=0 && input_ks.value.length!=20)
			{
				hlErrorField(input_ks,true)
				lf=1
			}

			if(lf)
			{
				buttons_toggle()
				return true
			}

			hlErrorField(input_bik,false)
			hlErrorField(input_rs,false)
			hlErrorField(input_ks,false)

			var bik_str=''
			if(input_ks.value!='')		bik_str=input_bik.value.substr(-3)
			else				bik_str='0'+input_bik.value.substr(4,2)

			if(test_str_schet(bik_str+input_rs.value))	//rs
			{
				hlErrorField(input_bik,true)
				hlErrorField(input_rs,true)
				buttons_toggle()
				return true
			}

			buttons_toggle()
			return true
		}
		hlErrorField(input_bik,false)
		hlErrorField(input_rs,false)
		hlErrorField(input_ks,false)
		input_ks.addEventListener( 'keyup', test_valid, false);
		input_rs.addEventListener( 'keyup', test_valid, false);
		input_bik.addEventListener( 'keyup', test_valid, false);
                input_ks.test_valid = test_valid;
                input_rs.test_valid = test_valid;
                input_bik.test_valid = test_valid;
		test_valid();
	}

	function validateOkpo(input)
	{
		function test_valid()
		{
                    if(!enabled) {
                        return true;
                    }
			if(input.value.length==0)
			{
				hlErrorField(input,false)
				buttons_toggle()
				return true
			}
			var i=0,kn1=0,kn2=0
			for(i=0;i<input.value.length-1;i++)
			{
				kn1+=(i%10+1)*input.value[i]
				kn2+=(i%10+3)*input.value[i]
			}
			kn1%=11
			kn2%=11
			if(kn1==Number(input.value[input.value.length-1]))
				hlErrorField(input,false)
			else if(kn2==Number(input.value[input.value.length-1]))
				hlErrorField(input,false)
			else if(kn1==10 && kn2==10 && Number(input.value[input.value.length-1]))
				hlErrorField(input,false)
			else	hlErrorField(input,true)
			buttons_toggle()
			return true
		}
		hlErrorField(input,false)
		input.addEventListener( 'keyup', test_valid, false);
                input.test_valid = test_valid;
		test_valid()
	}

	var input_bik=0,input_rs=0,input_ks=0
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
			else if(hasClass(form_inputs[i],'ks'))
				input_ks=form_inputs[i]
			else if(hasClass(form_inputs[i],'okpo'))
				validateOkpo(form_inputs[i], hasClass(form_inputs[i],'no_empty'))
			else if(hasClass(form_inputs[i],'inn'))
				validateINN(form_inputs[i], hasClass(form_inputs[i],'no_empty'))
		}
	}
	if(input_bik && input_rs && input_ks)
		validateBikRs(input_bik,input_rs, input_ks)
	return validator;
}
