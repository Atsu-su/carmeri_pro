<html>
<head>
<title>Hello</title>
</head>
<body>
    <form action="/test/activate" method="POST">
        @csrf
        <input type="text" name="email" value="">
        <input type="submit" value="Submit">
    </form>
</body>
</html>