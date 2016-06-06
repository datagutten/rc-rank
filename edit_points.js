var EFRA_GP2 = [0,75,71,67,63,61,59,57,55,53,51,49,48,47,46,45,44,43,42,41,40,39,38,37,36,35,34,33,32,31,30,29,28,27,26,25,24,23,22,21,20,19,18,17,16,15,14,13,12,11,10,9,8,7,6,5,4,3,2,1];
function update_points(PilotKey,rank)
{
	console.log('Points_hidden_'+PilotKey);
	document.getElementById('Points_'+PilotKey).textContent=EFRA_GP2[rank];
	document.getElementById('Points_hidden_'+PilotKey).value=EFRA_GP2[rank];
}
function show_pilotKey(pilotKey)
{
	document.getElementById('PilotKey_extra').textContent=pilotKey;
}