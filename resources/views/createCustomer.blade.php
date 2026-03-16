<!DOCTYPE html>
<html>

<head>
    <title>Create Zoho Customer</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <h2>Create Customer (Zoho Books)</h2>

    <form method="POST" action="/customer">
        @csrf

        <div>
            <label>Contact Name</label><br>
            <input type="text" name="contact_name" required>
        </div>

        <br>

        <div>
            <label>Company Name</label><br>
            <input type="text" name="company_name">
        </div>

        <br>

        <div>
            <label>Email</label><br>
            <input type="email" name="email">
        </div>

        <br>

        <div>
            <label>Phone</label><br>
            <input type="text" name="phone">
        </div>

        <br>

        <button type="submit">Create Customer</button>

    </form>


    @if (session('success'))
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script>
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: "{{ session('success') }}",
                showConfirmButton: false,
                timer: 3000
            });
        </script>
    @endif

</body>

</html>
