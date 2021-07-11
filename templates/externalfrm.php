<?php
script('stromquittung', 'script');
style('stromquittung', 'style');
?>
<iframe id="ifm" title="WebStromQuittung" src="https://corrently.de/service/quittung.html?embed=nextcloud<?php p($_['addquery']); ?>" width="100%" height="100%" style="width:100%;height:100%" allowfullscreen></iframe>
