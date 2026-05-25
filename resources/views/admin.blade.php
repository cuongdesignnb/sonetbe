<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin - CourseHub</title>
    <link rel="stylesheet" href="/admin/admin.css?v={{ file_exists(public_path('admin/admin.css')) ? filemtime(public_path('admin/admin.css')) : time() }}" />
  </head>
  <body>
    <div id="admin-app"></div>
    <script src="/admin/admin.js?v={{ file_exists(public_path('admin/admin.js')) ? filemtime(public_path('admin/admin.js')) : time() }}" defer></script>
  </body>
</html>
