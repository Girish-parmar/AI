<x-error-page
    code="419"
    title="Page expired"
    message="Your session expired for security reasons. Please go back and try again."
>
    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Go back</a>
</x-error-page>
