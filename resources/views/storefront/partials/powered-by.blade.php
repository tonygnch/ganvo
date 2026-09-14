{{--
 | "Powered by Ganvo" — the footer credit every theme carries.
 |
 | The link used to be assembled by hand in each layout as
 | "http://" . central_domain . ":8000", which is the local Docker proxy's port:
 | on a live shop it sent customers to https://ganvo.bg:8000, which does not
 | answer. marketing.home is bound to the central domain, so route() takes the
 | scheme and port of the request being served — http://ganvo.lvh.me:8000 in
 | development, https://ganvo.bg on sankevi.com — and there is one place to
 | change it.
 |
 | $linkStyle: optional inline style for themes that tint the brand link.
--}}
{!! __('site.common.powered_by', ['brand' => '<a href="'.e(route('marketing.home')).'" target="_blank" rel="noopener"'.(isset($linkStyle) ? ' style="'.e($linkStyle).'"' : '').'>Ganvo</a>']) !!}
