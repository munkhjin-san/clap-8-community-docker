<script>
    var target_board = {!! json_encode($target_board) !!};
    // user = JSON.parse(user);
    // Write data to local storage
    localStorage.setItem('target_board', JSON.stringify(target_board));

    // Redirect to another URL
    window.location.href = '{{ route("board") }}';
</script>