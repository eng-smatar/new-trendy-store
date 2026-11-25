@props(['amount'])
<span {{ $attributes }}><bdi dir="ltr">{{ number_format((float) $amount, 2) }}</bdi> ₪</span>
