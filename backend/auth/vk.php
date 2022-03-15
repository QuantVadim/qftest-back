<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Авторизация</title>
	<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>
<body>


<form id='form'>
	<input type="text" name="akey">
	<input type="text" name="name">
</form>


<script type="text/javascript">
	let token = getDataHash('access_token');
	let user_id =  getDataHash('user_id');
	document.forms.form.akey.value = token;
	document.forms.form.name.value = user_id;
function getDataHash(name) {
  	let matches = window.location.hash.slice(1).match(new RegExp(
    "(?:^|&)" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^&]*)"
  	));
  	return matches ? decodeURIComponent(matches[1]) : undefined;
}	

axios.post('../auth.php', {
	user_id: getDataHash('user_id'),
	access_token: getDataHash('access_token'),
	sn: 'vk'
}).then(respon => {
	console.log(respon);
	if(!respon.data?.error){
		document.cookie = "usrid="+respon.data.usr_id+"; max-age=36000; path=/";
		document.cookie = "mykey="+respon.data.mykey+"; max-age=36000; path=/";
		document.location = "/";
	}

});
//"https://api.vk.com/method/users.get?fields=first_name,last_name,photo_100&access_token="+token+"&v=5.52"


</script>
</body>
</html>