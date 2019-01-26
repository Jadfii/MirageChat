@extends('errors.illustrated-layout')

@section('code', '500')
@section('title', __('Error'))

@section('image')
    @if(app()->bound('sentry') && app('sentry')->getLastEventId())
        <script src="https://browser.sentry-cdn.com/4.5.3/bundle.min.js" crossorigin="anonymous"></script>
        <script>
            Sentry.init({ dsn: 'https://3197435afdfa48688b81d083c5004db0@sentry.io/1376197' });
            Sentry.showReportDialog({
                eventId: '{{ app('sentry')->getLastEventId() }}',
                'title': "You've encountered an error.",
                'subtitle': "If you'd like to help, tell us what happened below. If the error was not obvious, feel free to leave this form.",
                'subtitle2': "Error message: {{ $exception->getMessage() }}",
                @auth
                'user': {
                  'email': '{{ Auth()->user()->email }}',
                  'name': '{{ Auth()->user()->username }}',
                },
                @endauth
            });
        </script>
    @endif
    <div style="background-image: url({{ asset('/svg/500.svg') }});" class="absolute pin bg-cover bg-no-repeat md:bg-left lg:bg-center">
    </div>
@endsection

@section('message', __('Whoops, something went wrong on our servers.'))
