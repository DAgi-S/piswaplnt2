Please check line 1149 in your newreports.php file and look for anything like:

    Re-initialization of the DataTable inside drawCallback

    Recursive calls to a function without a base condition

    Any calls to DataTable().draw() inside the drawCallback itself




    Uncaught InternalError: too much recursion
    jQuery 11
jquery.min.js:2:11124
    jQuery 18
    drawCallback http://localhost/pistocklnt1march/production/newreports.php:1149
    jQuery 9
    drawCallback http://localhost/pistocklnt1march/production/newreports.php:1149
    jQuery 9
    drawCallback http://localhost/pistocklnt1march/production/newreports.php:1149
    jQuery 9
    drawCallback http://localhost/pistocklnt1march/production/newreports.php:1149
    jQuery 9
    drawCallback http://localhost/pistocklnt1march/production/newreports.php:1149
    jQuery 9
    drawCallback http://localhost/pistocklnt1march/production/newreports.php:1149
    jQuery 9
    drawCallback http://localhost/pistocklnt1march/production/newreports.php:1149
    jQuery 9
    drawCallback http://localhost/pistocklnt1march/production/newreports.php:1149
    jQuery 9
    drawCallback http://localhost/pistocklnt1march/production/newreports.php:1149
    jQuery 9
    drawCallback http://localhost/pistocklnt1march/production/newreports.php:1149
    jQuery 9
    drawCallback http://localhost/pistocklnt1march/production/newreports.php:1149
    jQuery 9
Uncaught InternalError: too much recursion
    jQuery 11
jquery.min.js:2:11124
    jQuery 21
    drawCallback http://localhost/pistocklnt1march/production/newreports.php:1149
    jQuery 9
    drawCallback http://localhost/pistocklnt1march/production/newreports.php:1149
    jQuery 9
    drawCallback http://localhost/pistocklnt1march/production/newreports.php:1149
    jQuery 9
    drawCallback http://localhost/pistocklnt1march/production/newreports.php:1149
    jQuery 9
    drawCallback http://localhost/pistocklnt1march/production/newreports.php:1149
    jQuery 9
    drawCallback http://localhost/pistocklnt1march/production/newreports.php:1149
    jQuery 9
    drawCallback http://localhost/pistocklnt1march/production/newreports.php:1149
    jQuery 9
    drawCallback http://localhost/pistocklnt1march/production/newreports.php:1149
    jQuery 9
    drawCallback http://localhost/pistocklnt1march/production/newreports.php:1149
    jQuery 9
    drawCallback http://localhost/pistocklnt1march/production/newreports.php:1149
    jQuery 9
    drawCallback http://localhost/pistocklnt1march/production/newreports.php:1149
    jQuery 6




    This error:
Uncaught InternalError: too much recursion
usually means that a function is calling itself over and over again (directly or indirectly) without stopping — basically, it's stuck in an infinite loop of function calls, which eventually crashes the browser’s JavaScript engine.
Your Case:

From your error trace, the issue seems to be tied to:

drawCallback http://localhost/pistocklnt1march/production/newreports.php:1149

That means the problem is happening inside the drawCallback function on line 1149 of newreports.php.

This drawCallback is likely being triggered repeatedly when rendering a DataTable (common with jQuery DataTables).
🔍 Possible Cause:

You might have something like:

$('#example').DataTable({
  drawCallback: function(settings) {
    $('#example').DataTable(); // ❌ this line re-initializes the table inside the drawCallback
  }
});

This is a classic cause of infinite recursion. Re-initializing the table inside the drawCallback causes another draw → another callback → and so on… crash.
✅ Solution:

Don't re-initialize the DataTable inside the drawCallback.

Instead, move your logic outside the callback, or make sure it's not creating a new table instance.

Safe example:

var table = $('#example').DataTable({
  drawCallback: function(settings) {
    // Do something like updating a UI element, but NOT re-initializing the table
    console.log("Drawn again.");
  }
});

👨‍🔧 Recommendation:

Please check line 1149 in your newreports.php file and look for anything like:

    Re-initialization of the DataTable inside drawCallback

    Recursive calls to a function without a base condition

    Any calls to DataTable().draw() inside the drawCallback itself

If you paste that part of your JS code here, I can help fix it directly.
