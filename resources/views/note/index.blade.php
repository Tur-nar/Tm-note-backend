<div>
    <a href="">New Note</a>
    <div>
        @foreach ($note as $notes)
        <div>
            <div>{{ $notes->title }}</div>
            <div>
                <a href="">view</a>
                <a href="">Edit</a>
                <a href="">Delete</a>
            </div>
        </div>
        @endforeach
    </div>
</div>
