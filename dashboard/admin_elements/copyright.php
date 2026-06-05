<?php declare(strict_types=1); 
use App\Core\DB;
?>
	<!-- Footer -->

	<footer class="navbar navbar-sm navbar-footer border-top" role="contentinfo">
		<div class="container-fluid">

			<span class="text-info mt-1">
				&copy; <?php echo date('Y'); ?> 
				<!-- <a href="https://www.haitechnologies.com" target="_blank">Hai Technologies LLC</a> -->
			</span>
			</span>

			<?php
			if (!function_exists('renderFooterBadge')) {
				function renderFooterBadge($label, $value, $color) {
					static $badge_id = 0;
					$badge_id++;
					$id = 'fb' . $badge_id;

					$label = htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8');
					$value = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');

					$label_width = max(40, (int)(6 * strlen($label) + 20));
					$value_width = max(40, (int)(6 * strlen($value) + 20));
					$total_width = $label_width + $value_width;

					$label_x = (int)($label_width / 2);
					$value_x = (int)($label_width + ($value_width / 2));

					$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $total_width . '" height="20" role="img" aria-label="' . $label . ': ' . $value . '">';
					$svg .= '<title>' . $label . ': ' . $value . '</title>';
					$svg .= '<linearGradient id="s' . $id . '" x2="0" y2="100%">';
					$svg .= '<stop offset="0" stop-color="#bbb" stop-opacity=".1" />';
					$svg .= '<stop offset="1" stop-opacity=".1" />';
					$svg .= '</linearGradient>';
					$svg .= '<clipPath id="r' . $id . '">';
					$svg .= '<rect width="' . $total_width . '" height="20" rx="3" fill="#fff" />';
					$svg .= '</clipPath>';
					$svg .= '<g clip-path="url(#r' . $id . ')">';
					$svg .= '<rect width="' . $label_width . '" height="20" fill="#555" />';
					$svg .= '<rect x="' . $label_width . '" width="' . $value_width . '" height="20" fill="' . $color . '" />';
					$svg .= '<rect width="' . $total_width . '" height="20" fill="url(#s' . $id . ')" />';
					$svg .= '</g>';
					$svg .= '<g fill="#fff" text-anchor="middle" font-family="Verdana,Geneva,DejaVu Sans,sans-serif" font-size="11">';
					$svg .= '<text x="' . $label_x . '" y="14">' . $label . '</text>';
					$svg .= '<text x="' . $value_x . '" y="14">' . $value . '</text>';
					$svg .= '</g>';
					$svg .= '</svg>';

					return $svg;
				}
			}

			$server_time = date('H:i:s');
			$php_version = PHP_VERSION;
			?>


			<ul class="nav">
                <!-- Last Login -->
                <?php
                $lastLogin = getTableAttrV('last_login', DB::USERS, " email = '" . addslashes($session_email ?? '') . "' ");
                $loginText = $lastLogin ? date('M j, Y g:i A', strtotime($lastLogin)) : 'Never';
                ?>
                <li class="nav-item">
                    <small style="display: flex; align-items: center; gap: 6px;">
                        <i class="ph-clock-counter-clockwise" style="font-size: 14px;"></i>
                        <em>Last Login:</em> <strong><?php echo htmlspecialchars($loginText); ?></strong>
                    </small>
                </li>

				<li class="nav-item ms-2">
					<?php echo renderFooterBadge('time', $server_time, '#007ec6'); ?>
				</li>
				<li class="nav-item ms-2">
					<?php echo renderFooterBadge('php', $php_version, '#4c1'); ?>
				</li>

				<!-- <li class="nav-item">
					<a href="#" class="navbar-nav-link navbar-nav-link-icon rounded" target="_blank">
						<div class="d-flex align-items-center mx-md-1">
							<i class="ph-lifebuoy"></i>
							<span class="d-none d-md-inline-block ms-2">Support</span>
						</div>
					</a>
				</li> -->
				<!-- <li class="nav-item ms-md-1">
					<a href="https://demo.interface.club/limitless/demo/Documentation/index.html" class="navbar-nav-link navbar-nav-link-icon rounded" target="_blank">
						<div class="d-flex align-items-center mx-md-1">
							<i class="ph-file-text"></i>
							<span class="d-none d-md-inline-block ms-2">Videos</span>
						</div>
					</a>
				</li> -->
				<!-- <li class="nav-item ms-md-1">
					<a href="https://themeforest.net/item/limitless-responsive-web-application-kit/13080328?ref=kopyov" class="navbar-nav-link navbar-nav-link-icon text-primary bg-primary bg-opacity-10 fw-semibold rounded" target="_blank">
						<div class="d-flex align-items-center mx-md-1">
							<i class="ph-shopping-cart"></i>
							<span class="d-none d-md-inline-block ms-2">Help Center</span>
						</div>
					</a>
				</li> -->
			</ul>
		</div>
	</footer>

	<!-- 	
	<div class="navbar navbar-sm navbar-footer border-top mt-3">
		<div class="container-fluid">
			<span>
				&copy; <?php echo date('Y'); ?>
				&nbsp; &nbsp; <small><em>Page Generated in</em></small> <?php echo loading_time($start_page_time); ?></p>
			</span>

		</div>
	</div> -->
	<!-- /footer -->