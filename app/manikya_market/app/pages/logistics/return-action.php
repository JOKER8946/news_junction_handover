<?php

declare(strict_types=1);

// Return actions were removed from the logistics flow. The route is kept so
// any stale bookmark or in-flight form post lands on the dashboard instead of
// a 404. (The previous version of this file had a duplicated <?php tag and an
// orphaned catch body, which made it a fatal parse error on every hit.)
redirect_to('logistics/dashboard');
