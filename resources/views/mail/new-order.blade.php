<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head><meta charset="utf-8"></head>
<body style="font-family: Tahoma, Arial, sans-serif; color: #1d1a22; line-height: 1.7">
    <h1 style="font-size: 20px">{{ __('admin.mail.heading') }}</h1>
    <p>{{ __('admin.reference') }}: <bdi dir="ltr"><strong>{{ $order->reference }}</strong></bdi></p>
    <p>{{ __('admin.total') }}: <bdi dir="ltr">{{ number_format((float) $order->customer_total, 2) }}</bdi> ₪</p>
    <p>{{ __('admin.mail.town') }}: {{ $order->town_name }}، {{ $order->district_name }}</p>
    @unless ($order->quote_verified)
        <p>{{ __('admin.unverified') }}</p>
    @endunless
    <p><a href="{{ $url }}" style="display: inline-block; background: #3f2878; color: #fff; padding: 10px 20px; border-radius: 8px; text-decoration: none">{{ __('admin.mail.review') }}</a></p>
</body>
</html>
