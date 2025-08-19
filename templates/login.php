<!DOCTYPE html>
<html>
<head>

<title>PHP-Proxy - Login Required</title>

<meta name="generator" content="php-proxy.com">
<meta name="version" content="<?=$version;?>">

<style type="text/css">
html body {
	font-family: Arial,Helvetica,sans-serif;
	font-size: 12px;
}

#container {
	width:500px;
	margin:0 auto;
	margin-top:150px;
}

#error {
	color:red;
	font-weight:bold;
}

#frm {
	padding:10px 15px;
	background-color:#E8F5E8;
	
	border:1px solid #818181;
	
	-webkit-border-radius: 8px;
	-moz-border-radius: 8px;
	border-radius: 8px;
}

#footer {
	text-align:center;
	font-size:10px;
	margin-top:35px;
	clear:both;
}

.auth-info {
	background-color:#F0F8FF;
	padding:10px;
	margin-bottom:15px;
	border:1px solid #B0C4DE;
	border-radius: 5px;
	font-size:11px;
}
</style>

</head>

<body>


<div id="container">

	<div style="text-align:center;">
		<h1 style="color:green;">🔐 Authentication Required</h1>
	</div>
	
	<div class="auth-info">
		<strong>Access Restricted:</strong> This proxy requires a valid login code provided by the administrator. 
		Please enter your authorization code below to continue.
	</div>
	
	<?php if(isset($error_msg)){ ?>
	
	<div id="error">
		<p><?php echo strip_tags($error_msg); ?></p>
	</div>
	
	<?php } ?>
	
	<div id="frm">
	
	<!-- Login Form -->
	
		<form action="index.php" method="post" style="margin-bottom:0;">
			<label for="login_code" style="display:block; margin-bottom:5px; font-weight:bold;">Authorization Code:</label>
			<input name="login_code" type="text" style="width:400px; padding:5px;" autocomplete="off" placeholder="Enter your login code" required />
			<br><br>
			<input type="submit" value="Login" style="padding:8px 20px;">
		</form>
		
		<script type="text/javascript">
			document.getElementsByName("login_code")[0].focus();
		</script>
		
	<!-- [END] -->
	
	</div>
	
</div>

<div id="footer">
	Powered by <a href="//www.php-proxy.com/" target="_blank">PHP-Proxy</a> <?php echo $version; ?> | Secure Access
</div>


</body>
</html>