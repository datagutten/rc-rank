function selector(name,choices,next_step,level)
{
	//console.log(json);
	//var items=JSON.parse(json);
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
		selector=document.createElement('select');
	selector.setAttribute('name',name);
	selector.setAttribute('id',name);
	selector.setAttribute('data-level',level);

	if(next_step!='submit')
		selector.setAttribute('onChange','add_selector("'+next_step+'",this)');
	else
		selector.setAttribute('onChange','submit_button(this.value)');
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
		option.textContent=choices[choice_key];
		selector.appendChild(option);
	}

	return selector;
}
function add_selector(mode,previous,next_step)
{
	var param=previous.value
	var step=document.getElementById(next_step);
	var next_step_object=document.getElementById(next_step);
	//If param is false remove all higher level objects
	//If param is valid rewrite next object and remove higher than that

	//Remove higher level steps
	if(previous!='')
	{
		var level=previous.getAttribute('data-level');
		console.log('Remove objects higher than level '+level);
		//var next_level=next_step_object.getAttribute('data-level');
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
				if(next_step==undefined)
					next_step=response.next_step;
				var select_input=selector(mode,response.data,next_step,response.level);
				document.getElementsByTagName('form').item(0).appendChild(select_input);
			}
		};
		var federation=document.getElementById('federation').value;
		xmlhttp.open('GET','selector_backend.php?federation='+federation+'&mode='+mode+'&param='+param,true);
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
	for(i=inputs.length-1; i>=0; i--)
	{
		var level=inputs[i].getAttribute('data-level');

		if(level>current_level)
		{
			inputs[i].parentNode.removeChild(inputs[i]);
		}
	}
}