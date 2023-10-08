
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>YoPrint CSV Demo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
</head>
<body>
<section class="py-5 text-center container">
    @include('elements.message')
    <div class="row py-lg-5">
        <div class="col-lg-6 col-md-8 mx-auto">
            <form action="{{route('csv.upload')}}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-8">
                        <input type="file" name="csvFile" >
                    </div>
                    <div class="col-md-4">
                        <input type="submit" class="btn btn-primary" value="Upload File">
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<div class="album py-5 bg-light">
    <div class="container">
        <table class="table table-striped">
            <thead>
            <tr>
                <th scope="col">Time</th>
                <th scope="col">File Name</th>
                <th scope="col">Status</th>
            </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>
