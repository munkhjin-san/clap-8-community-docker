<script>
    var user = {!! json_encode($invite_user) !!};
    // user = JSON.parse(user);
    // Write data to local storage
    localStorage.setItem('invite_user', JSON.stringify(user));

    // Redirect to another URL
    // window.location.href = '/';
    location.replace(window.location.origin + '/chat')
</script>