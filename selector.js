"use strict";
function selector(name,choices,next_step,level)
{
	//console.log(json);
	//var items=JSON.parse(json);
	var url_parameters=decodeURIComponent(window.location.search);
	var selector=document.getElementById(name);
	if(selector!=undefined)
	{
		//for(child in selector.childNodes)
		for(var child_id=0;child_id<selector.childNodes.length; child_id++)
		{
			//console.log(child_id);
			//selector.removeChild(selector.childNodes.item(child));
			selector.removeChild(selector.childNodes.item(child_id));
		}
	}
	else
	{
		selector=document.createElement('select');
	}
	selector.setAttribute('name',name);
	selector.setAttribute('id',name);
	selector.setAttribute('data-level',level);

	if(next_step!=='submit')
	{
		selector.setAttribute('onChange','add_selector("'+next_step+'",this)');
	}
	else
	{
		selector.setAttribute('onChange','submit_button(this.value)');
	}
	var default_option=document.createElement('option');
	default_option.setAttribute('value','false');
	default_option.textContent='Select...';
	selector.appendChild(default_option);
	for (var choice_key in choices)
	{
		//console.log(choices[i]);
		//console.log(choice);
		var option=document.createElement('option');
		option.setAttribute('value',choice_key);
		option.setAttribute('id','option_'+name+'_'+choice_key);
		option.textContent=choices[choice_key];
		if(url_parameters.indexOf(name+'='+choice_key)>0) //Make the current item preselected if the value is in the url
		{
			option.setAttribute('selected','selected');
		}
		
		selector.appendChild(option);
	}

	return selector;
}
function add_selector(mode,previous,next_step,selector_name=false)
{
	var param=previous.value;
	var previous_values='';
	var url_parameters=decodeURIComponent(window.location.search);
	//If param is false remove all higher level objects
	//If param is valid rewrite next object and remove higher than that

	//Remove higher level steps
	if(previous!='')
	{
		var level=previous.getAttribute('data-level');
		console.log('Remove objects higher than level '+level);
		if(level>1)
		{
			previous_values=get_lower_levels(level);
		}
		remove_higher_levels(level);
	}
	if(param!='false')
	{
		var xmlhttp=new XMLHttpRequest();
		xmlhttp.onreadystatechange = function()
		{
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
			{
				//document.getElementById("demo").innerHTML = xhttp.responseText;
				//console.log(xmlhttp.responseText);
				var response=JSON.parse(xmlhttp.responseText);
				if(next_step===undefined)
				{
					next_step=response.next_step;
				}
				if(selector_name===false)
				{
					selector_name=mode;
				}
				var select_input=selector(selector_name,response.data,next_step,response.level);
				document.getElementsByTagName('form').item(0).appendChild(select_input);

				if(next_step!=='submit' && url_parameters.indexOf(selector_name+'=')>0) //Call add_selector again if the current field has a value in the url
				{
					add_selector(next_step,select_input);
				}
			}
		};

		xmlhttp.open('GET','selector_backend.php?mode='+mode+'&param='+param+previous_values,true);
		xmlhttp.send();
	}
}
function submit_button(value)
{
	//console.log(value);
	var submit_input=document.createElement('input');
	submit_input.setAttribute('type','submit');
	submit_input.setAttribute('value','Submit');
	document.getElementsByTagName('form').item(0).appendChild(submit_input);
}
function remove_higher_levels(current_level)
{
	var inputs=document.getElementsByTagName('select');
	for(var i=inputs.length-1; i>=0; i--)
	{
		var level=inputs[i].getAttribute('data-level');

		if(level>current_level)
		{
			inputs[i].parentNode.removeChild(inputs[i]);
		}
	}
}
function get_lower_levels(current_level)
{
	var inputs=document.getElementsByTagName('select');
	var values='';
	for(var i=inputs.length-1; i>=0; i--)
	{
		var level=inputs[i].getAttribute('data-level');

		if(level<current_level)
		{
			values+='&'+inputs[i].name+'='+inputs[i].value;
		}
	}
	return values;
}