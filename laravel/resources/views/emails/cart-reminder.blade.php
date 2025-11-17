@php($stepLabel = $step->label())
<p>Hello,</p>
<p>
    We noticed you left some items in your cart. This is your {{ $stepLabel }} reminder to
    complete the checkout process.
</p>
<p>
    <a href="{{ url('/cart/'.$cart->getId()) }}">Click here to return to your cart</a>
    and finalize your order.
</p>
<p>Thank you,<br>{{ config('app.name') }}</p>

