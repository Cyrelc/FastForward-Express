Click here to reset your password: <a href="{{ $link = URL::signedRoute('password/reset', $token).'?email='.urlencode($user->getEmailForPasswordReset()) }}"> {{ $link }} </a>
