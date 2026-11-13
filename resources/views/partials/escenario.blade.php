{{-- Escenario lavanda en código: esferas de vidrio, trazos, destellos y nubes --}}
@php($c = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 0c.6 6.4 5.6 11.4 12 12-6.4.6-11.4 5.6-12 12-.6-6.4-5.6-11.4-12-12C6.4 11.4 11.4 6.4 12 0z"/></svg>')
<div class="deco" aria-hidden="true">
    <span class="esfera" style="width: 640px; height: 640px; left: -210px; top: -400px"></span>
    <span class="esfera" style="width: 520px; height: 520px; right: -150px; top: -190px"></span>
    <span class="trazo" style="width: 1500px; height: 900px; left: -780px; top: -140px; transform: rotate(-16deg)"></span>
    <span class="trazo" style="width: 1500px; height: 900px; left: -720px; top: -80px; transform: rotate(-16deg); opacity: .55"></span>
    <span class="chispa" style="width: 38px; left: 47%; top: 10%">{!! $c !!}</span>
    <span class="chispa" style="width: 18px; left: 46%; top: 19%; animation-delay: 1.2s">{!! $c !!}</span>
    <span class="chispa" style="width: 34px; left: 71%; top: 19%; animation-delay: .6s">{!! $c !!}</span>
    <span class="chispa" style="width: 46px; left: 50%; top: 50%; animation-delay: 1.8s">{!! $c !!}</span>
    <span class="chispa" style="width: 26px; left: 2.5%; top: 21%; animation-delay: 2.4s">{!! $c !!}</span>
    <span class="chispa" style="width: 26px; left: 1.5%; top: 63%; animation-delay: .9s">{!! $c !!}</span>
    <span class="chispa" style="width: 18px; left: 36%; top: 82%; animation-delay: 1.5s">{!! $c !!}</span>
    <span class="chispa" style="width: 16px; right: 10%; top: 9%; animation-delay: 2s">{!! $c !!}</span>
    <span class="chispa lila" style="width: 44px; right: 7%; top: 28%; animation-delay: .3s">{!! $c !!}</span>
    <span class="chispa lila" style="width: 30px; right: 2.5%; top: 46%; animation-delay: 1.1s">{!! $c !!}</span>
    <span class="chispa lila" style="width: 26px; left: 2%; top: 42%; animation-delay: 2.6s">{!! $c !!}</span>
    <span class="chispa lila" style="width: 30px; left: 41%; top: 77%; animation-delay: .7s">{!! $c !!}</span>
    <span class="chispa" style="width: 22px; left: 50%; top: 83%; animation-delay: 2.2s">{!! $c !!}</span>
    {{-- Nubes: burbujas de vidrio superpuestas --}}
    <span class="nube" style="width: 460px; height: 460px; left: -230px; bottom: 30px"></span>
    <span class="nube" style="width: 420px; height: 420px; left: -120px; bottom: -200px"></span>
    <span class="nube" style="width: 360px; height: 360px; left: 230px; bottom: -230px"></span>
    <span class="nube" style="width: 520px; height: 520px; left: 33%; bottom: -380px"></span>
    <span class="nube" style="width: 460px; height: 460px; left: 54%; bottom: -330px"></span>
    <span class="nube" style="width: 420px; height: 420px; right: 4%; bottom: -260px"></span>
    <span class="nube" style="width: 360px; height: 360px; right: -150px; bottom: 140px"></span>
    <span class="nube" style="width: 420px; height: 420px; right: -170px; bottom: -150px"></span>
</div>
