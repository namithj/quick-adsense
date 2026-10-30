<?php
/**
 * Named ad payloads shared by the user-config regression fixtures.
 *
 * Every payload carries a unique QAFX marker comment so snapshots stay
 * readable. Line endings are written as explicit escapes (never as raw CR bytes
 * in this file) so Git's text normalisation cannot change the fixture bytes.
 *
 * @return array<string, string> Payload name => exact ad code.
 */

$qa_ad_codes = [];

// Modern async AdSense, slots 1 to 10.
for ( $qa_n = 1; $qa_n <= 10; $qa_n++ ) {
	$qa_ad_codes[ 'adsense-async-' . $qa_n ] = '<!-- QAFX:onpost-' . $qa_n . ' -->' . "\n"
		. '<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1111111111111111" crossorigin="anonymous"></script>' . "\n"
		. '<ins class="adsbygoogle" style="display:block" data-ad-client="ca-pub-1111111111111111" data-ad-slot="' . sprintf( '%02d00000000', $qa_n ) . '" data-ad-format="auto" data-full-width-responsive="true"></ins>' . "\n"
		. '<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>';

	// Sidebar widget payloads: distinct from the on-post ones, same shape.
	$qa_ad_codes[ 'widget-' . $qa_n ] = '<!-- QAFX:widget-' . $qa_n . ' -->' . "\n"
		. '<ins class="adsbygoogle" style="display:inline-block;width:300px;height:250px" data-ad-client="ca-pub-1111111111111111" data-ad-slot="' . sprintf( '%02d11111111', $qa_n ) . '"></ins>' . "\n"
		. '<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>';
}

$qa_ad_codes['adsense-legacy'] = '<!-- QAFX:adsense-legacy -->' . "\n"
	. '<script type="text/javascript"><!--' . "\n"
	. 'google_ad_client = "pub-2222222222222222";' . "\n"
	. 'google_ad_slot = "2222222222";' . "\n"
	. 'google_ad_width = 300;' . "\n"
	. 'google_ad_height = 250;' . "\n"
	. '//-->' . "\n"
	. '</script>' . "\n"
	. '<script type="text/javascript" src="https://pagead2.googlesyndication.com/pagead/show_ads.js"></script>';

$qa_ad_codes['adsense-auto-ads'] = '<!-- QAFX:adsense-auto-ads -->' . "\n"
	. '<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3333333333333333" crossorigin="anonymous"></script>';

$qa_ad_codes['gam-gpt'] = '<!-- QAFX:gam-gpt -->' . "\n"
	. '<script async src="https://securepubads.g.doubleclick.net/tag/js/gpt.js"></script>' . "\n"
	. '<script>' . "\n"
	. '  window.googletag = window.googletag || {cmd: []};' . "\n"
	. '  googletag.cmd.push(function() {' . "\n"
	. "    googletag.defineSlot('/1234567/qa-fixture', [300, 250], 'div-gpt-ad-qa-1').addService(googletag.pubads());\n"
	. '    googletag.enableServices();' . "\n"
	. '  });' . "\n"
	. '</script>' . "\n"
	. '<div id="div-gpt-ad-qa-1" style="min-width:300px;min-height:250px;"><script>googletag.cmd.push(function() { googletag.display(\'div-gpt-ad-qa-1\'); });</script></div>';

$qa_ad_codes['amazon-iframe'] = '<!-- QAFX:amazon-iframe -->' . "\n"
	. '<iframe src="https://ws-na.amazon-adsystem.com/widgets/q?ServiceVersion=20070822&OneJS=1&Operation=GetAdHtml&MarketPlace=US&source=ac&ref=qf_sp_asin_til&ad_type=product_link&tracking_id=qa-20" width="120" height="240" scrolling="no" frameborder="0" style="border:none;"></iframe>';

$qa_ad_codes['medianet'] = '<!-- QAFX:medianet -->' . "\n"
	. '<div id="123456789">' . "\n"
	. '<script type="text/javascript">' . "\n"
	. '    try {' . "\n"
	. '        window._mNHandle.queue.push(function (){' . "\n"
	. '            window._mNDetails.loadTag("123456789", "300x250", "123456789");' . "\n"
	. '            var label = "it\'s a \"quoted\" label";' . "\n"
	. '        });' . "\n"
	. '    }' . "\n"
	. '    catch (error) {}' . "\n"
	. '</script>' . "\n"
	. '</div>';

$qa_ad_codes['analytics-gtag'] = '<!-- QAFX:analytics-gtag -->' . "\n"
	. '<script async src="https://www.googletagmanager.com/gtag/js?id=G-QAFIXTURE1"></script>' . "\n"
	. '<script>' . "\n"
	. '  window.dataLayer = window.dataLayer || [];' . "\n"
	. '  function gtag(){dataLayer.push(arguments);}' . "\n"
	. "  gtag('js', new Date());\n"
	. "  gtag('config', 'G-QAFIXTURE1');\n"
	. '</script>';

// Hostile bytes: a leading and trailing newline, CRLF line endings, an early
// textarea terminator, backslashes, both quote styles, entities, unicode, a
// shortcode and PHP open tag as plain text, and a nested position marker.
$qa_ad_codes['nasty'] = "\r\n"
	. '<!-- QAFX:nasty -->' . "\r\n"
	. '<!--Ads2-->' . "\r\n"
	. '</textarea><script>var p = \'C:\\\\ads\\\\x\'; var q = "say \\"hi\\"";</script>' . "\r\n"
	. '<div title="a &amp; b" data-x=\'it"s\'>ünï 漢字 🚀 &amp; "q" \'s\' \\o/</div>' . "\r\n"
	. '[gallery]' . "\r\n"
	. '<?php echo "not executed"; ?>' . "\r\n";

$qa_ad_codes['empty']      = '';
$qa_ad_codes['whitespace'] = "  \n ";

return $qa_ad_codes;
