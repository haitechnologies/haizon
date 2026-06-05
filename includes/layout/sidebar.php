<aside class="app-sidebar doc-sidebar my-dash">
								<div class="app-sidebar__user clearfix">
									<ul class="side-menu">
										<li>
											<a class="side-menu__item<?php if ($_SERVER['REQUEST_URI'] == '/account/profile') echo ' active'; ?>" href="<?php echo url('/account/profile'); ?>"><i class="typcn typcn-edit fs-20"></i><span class="side-menu__label ms-2">Edit Profile</span></a>
										</li>
										<li class="slide">
											<a class="side-menu__item" data-bs-toggle="slide" href="javascript:void(0)"><i class="typcn typcn-heart-outline fs-20"></i><span class="side-menu__label ms-2">My Favorite</span><i class="angle fa fa-angle-right"></i></a>
											<ul class="slide-menu">
														<li><a class="slide-item" href="<?php echo url('/my-favorites'); ?>"><i class="fa fa-angle-right me-2"></i>Favorite1</a></li>
										        	<li><a class="slide-item" href="<?php echo url('/underconstruction'); ?>"><i class="fa fa-angle-right me-2"></i>Favorite2</a></li>
											</ul>
										</li>
										<li class="slide">
											<a class="side-menu__item" data-bs-toggle="slide" href="javascript:void(0)"><i class="typcn typcn-briefcase fs-20"></i><span class="side-menu__label ms-2">My Listings</span><i class="angle fa fa-angle-right"></i></a>
											<ul class="slide-menu">
														<li><a class="slide-item" href="<?php echo url('/account/profile?tab=listing'); ?>"><i class="fa fa-angle-right me-2"></i>My-Listing 01</a></li>
												<li><a class="slide-item" href="<?php echo url('/underconstruction'); ?>"><i class="fa fa-angle-right me-2"></i>My-Listing 02</a></li>
											</ul>
										</li>
										<li class="slide">
											<a class="side-menu__item" data-bs-toggle="slide" href="javascript:void(0)"><i class="typcn typcn-folder fs-20"></i><span class="side-menu__label ms-2">Managed Listings</span><i class="angle fa fa-angle-right"></i></a>
											<ul class="slide-menu">
												<li><a class="slide-item" href="<?php echo url('/underconstruction'); ?>"><i class="fa fa-angle-right me-2"></i></i>Managed Listing 01</a></li>
												<li><a class="slide-item" href="<?php echo url('/underconstruction'); ?>"><i class="fa fa-angle-right me-2"></i></i>Managed Listing 02</a></li>
												<li class="sub-slide">
													<a class="side-menu__item border-top-0 slide-item" href="javascript:void(0)" data-bs-toggle="sub-slide"><span class="side-menu__label"><i class="fa fa-angle-right me-2"></i>Listings</span> <i class="sub-angle fa fa-angle-right"></i></a>
													<ul class="child-sub-menu ">
														<li><a class="slide-item" href="javascript:void(0)"><i class="fa fa-angle-right me-2 text-muted"></i>Managed Listing </a></li>
													</ul>
												</li>
											</ul>
										</li>
										<li>
											<a class="side-menu__item" href="<?php echo url('/underconstruction'); ?>"><i class="typcn typcn-credit-card fs-20"></i><span class="side-menu__label ms-2">Payments</span></a>
										</li>
										<li>
											<a class="side-menu__item" href="<?php echo url('/underconstruction'); ?>"><i class="typcn typcn-shopping-cart fs-20"></i><span class="side-menu__label ms-2">Orders</span></a>
										</li>
										<li>
											<a class="side-menu__item" href="<?php echo url('/tips'); ?>"><i class="typcn typcn-flag-outline fs-20"></i><span class="side-menu__label ms-2">Safety Tips</span></a>
										</li>
										<li>
											<a class="side-menu__item" href="<?php echo url('/account/settings'); ?>"><i class="typcn typcn-cog-outline fs-20"></i><span class="side-menu__label ms-2">Settings </span></a>
										</li>
										<li>
											<a class="side-menu__item" href="<?php echo url('/logout'); ?>"><i class="typcn typcn-power-outline fs-20"></i><span class="side-menu__label ms-2">Logout</span></a>
										</li>
									</ul>
								</div>
							</aside>