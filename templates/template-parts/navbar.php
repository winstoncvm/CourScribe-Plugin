<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

$current_user = wp_get_current_user();
$user_avatar = get_avatar_url($current_user->ID, array('size' => 48));
$user_name = $current_user->display_name;
$current_user_id = get_current_user_id();

?>
<nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-none border-radius-xl" id="navbarBlur" navbar-scroll="true">
	<div class="container-fluid py-1 px-3">
	<div id="site-logo" class="site-branding buddypanel_logo_display_off">
		<div class="site-title">
			<a href="<?= home_url(); ?>/" rel="home">
				<img width="140" height="27" src="<?= home_url(); ?>/wp-content/uploads/2024/12/logo.png" class="bb-logo" alt="" decoding="async">		</a>
		</div>
	</div>
		<div class="collapse navbar-collapse mt-sm-0 mt-2 me-md-0 me-sm-4" id="navbar">
			<div class="ms-md-auto pe-md-3 d-flex align-items-center">
				<!-- Placeholder for future content -->
				
				<ul class="navbar-nav justify-content-end">

			
			<li id="menu-item-159" class="nav-item d-flex align-items-center bp-menu bp-groups-nav menu-item menu-item-type-bp_nav menu-item-object-bp_loggedin_nav menu-item-159 no-icon">
				<a href="<?= home_url(); ?>/members/winston/groups/?customize_changeset_uuid=ae82bf3d-6282-4cac-ab82-dbf8384b610d&amp;customize_autosaved=on">
					<span>Studios</span>
				</a>
			</li>
			<li id="menu-item-157" class="nav-item d-flex align-items-center bp-menu bp-activity-nav menu-item menu-item-type-bp_nav menu-item-object-bp_loggedin_nav menu-item-157 no-icon"><a href="<?= home_url(); ?>/members/winston/activity/?customize_changeset_uuid=ae82bf3d-6282-4cac-ab82-dbf8384b610d&amp;customize_autosaved=on"><span>Activity</span></a></li>
			<li id="menu-item-153" class="nav-item d-flex align-items-center bp-menu bp-profile-nav menu-item menu-item-type-bp_nav menu-item-object-bp_loggedin_nav menu-item-153 no-icon"><a href="<?= home_url(); ?>/members/winston/profile/?customize_changeset_uuid=ae82bf3d-6282-4cac-ab82-dbf8384b610d&amp;customize_autosaved=on"><span>Profile</span></a></li>
			<li id="menu-item-154" class="nav-item d-flex align-items-center bp-menu bp-settings-nav menu-item menu-item-type-bp_nav menu-item-object-bp_loggedin_nav menu-item-154 no-icon"><a href="<?= home_url(); ?>/members/winston/settings/?customize_changeset_uuid=ae82bf3d-6282-4cac-ab82-dbf8384b610d&amp;customize_autosaved=on"><span>Settings</span></a></li>
			<li id="menu-item-151" class="nav-item d-flex align-items-center menu-item menu-item-type-post_type menu-item-object-page menu-item-151 no-icon"><a href="<?= home_url(); ?>/wp-login.php?action=logout&amp;_wpnonce=627bd75408"><span>Logout</span></a></li>

			<li class="nav-item d-xl-none ps-3 d-flex align-items-center">
			<a href="javascript:;" class="nav-link text-body p-0" id="iconNavbarSidenav">
			<div class="sidenav-toggler-inner">
			<i class="sidenav-toggler-line"></i>
			<i class="sidenav-toggler-line"></i>
			<i class="sidenav-toggler-line"></i>
			</div>
			</a>
			</li>

		</ul>
			</div>
			<ul class="navbar-nav justify-content-end">
				<li class="d-flex align-items-center">
					<button class="continue-application " data-bs-toggle="modal" data-bs-target="#createCurriculumModal" style="margin-right: 40px;">
						<div>
							<div class="pencil"></div>
							<div class="folder">
								<div class="top">
									<svg viewBox="0 0 24 27">
										<path d="M1,0 L23,0 C23.5522847,-1.01453063e-16 24,0.44771525 24,1 L24,8.17157288 C24,8.70200585 23.7892863,9.21071368 23.4142136,9.58578644 L20.5857864,12.4142136 C20.2107137,12.7892863 20,13.2979941 20,13.8284271 L20,26 C20,26.5522847 19.5522847,27 19,27 L1,27 C0.44771525,27 6.76353751e-17,26.5522847 0,26 L0,1 C-6.76353751e-17,0.44771525 0.44771525,1.01453063e-16 1,0 Z"></path>
									</svg>
								</div>
								<div class="paper"></div>
							</div>
						</div>

						Create New Curriculum
					</button>

				</li>
				<li class="d-flex align-items-center dropdown pe-2">
					<a href="javascript:;" class="nav-link text-body font-weight-bold px-0" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
						<img src="<?php echo esc_url($user_avatar); ?>" class="avatar avatar-sm me-3" alt="User Avatar">
						<span class="d-sm-inline d-none"><?php echo esc_html($user_name); ?></span>
					</a>
					<ul class="dropdown-menu dropdown-menu-end px-2 py-3 me-sm-n4" aria-labelledby="dropdownMenuButton">
						<li class="mb-2">
							<a class="user-link" href="<?= home_url(); ?>/members/winston/?customize_changeset_uuid=ae82bf3d-6282-4cac-ab82-dbf8384b610d&amp;customize_autosaved=on">
								<img alt="" src="<?= home_url(); ?>/wp-content/plugins/buddypress/bp-core/images/mystery-man.jpg" srcset="<?= home_url(); ?>/wp-content/plugins/buddypress/bp-core/images/mystery-man.jpg 2x" class="avatar avatar-100 photo" height="100" width="100" decoding="async">										<span>
									<span class="user-name">Winston</span>
																					
									<span class="user-mention">@winston</span>
																			
								</span>
							</a>
						</li>
						<li id="wp-admin-bar-my-account-xprofile" class="mb-2 menupop parent">
							<a class="ab-item" aria-haspopup="true" href="<?= home_url(); ?>/members/winston/profile/?customize_changeset_uuid=ae82bf3d-6282-4cac-ab82-dbf8384b610d&amp;customize_autosaved=on">
								<i class="bb-icon-l bb-icon-user-avatar"></i>
								<span class="wp-admin-bar-arrow" aria-hidden="true"></span>Profile			</a>
							<div class="ab-sub-wrapper wrapper">
								<ul id="wp-admin-bar-my-account-xprofile-default" class="ab-submenu">
									<li id="wp-admin-bar-my-account-xprofile-public">
										<a class="ab-item" href="<?= home_url(); ?>/members/winston/profile/?customize_changeset_uuid=ae82bf3d-6282-4cac-ab82-dbf8384b610d&amp;customize_autosaved=on">View</a>
									</li>
									<li id="wp-admin-bar-my-account-xprofile-edit">
										<a class="ab-item" href="<?= home_url(); ?>/members/winston/profile/edit/?customize_changeset_uuid=ae82bf3d-6282-4cac-ab82-dbf8384b610d&amp;customize_autosaved=on">Edit</a>
									</li>
														<li id="wp-admin-bar-my-account-xprofile-change-avatar">
										<a class="ab-item" href="<?= home_url(); ?>/members/winston/profile/change-avatar/?customize_changeset_uuid=ae82bf3d-6282-4cac-ab82-dbf8384b610d&amp;customize_autosaved=on">Profile Photo</a>
									</li>
																			<li id="wp-admin-bar-my-account-xprofile-change-cover-image">
										<a class="ab-item" href="<?= home_url(); ?>/members/winston/profile/change-cover-image/?customize_changeset_uuid=ae82bf3d-6282-4cac-ab82-dbf8384b610d&amp;customize_autosaved=on">Cover Photo</a>
									</li>
													</ul>
							</div>
						</li>
						<li id="wp-admin-bar-my-account-settings" class="mb-2 menupop parent">
							<a class="ab-item" aria-haspopup="true" href="<?= home_url(); ?>/members/winston/settings/?customize_changeset_uuid=ae82bf3d-6282-4cac-ab82-dbf8384b610d&amp;customize_autosaved=on">
								<i class="bb-icon-l bb-icon-user"></i>
								<span class="wp-admin-bar-arrow" aria-hidden="true"></span>Account			</a>
							<div class="ab-sub-wrapper wrapper">
								<ul id="wp-admin-bar-my-account-settings-default" class="ab-submenu">
									<li id="wp-admin-bar-my-account-settings-general">
										<a class="ab-item" href="<?= home_url(); ?>/members/winston/settings/?customize_changeset_uuid=ae82bf3d-6282-4cac-ab82-dbf8384b610d&amp;customize_autosaved=on">
											Login Information						</a>
									</li>
														<li id="wp-admin-bar-my-account-settings-notifications">
										<a class="ab-item" href="<?= home_url(); ?>/members/winston/settings/notifications/?customize_changeset_uuid=ae82bf3d-6282-4cac-ab82-dbf8384b610d&amp;customize_autosaved=on">
											Email Preferences						</a>
									</li>
														<li id="wp-admin-bar-my-account-settings-profile">
										<a class="ab-item" href="<?= home_url(); ?>/members/winston/settings/profile/?customize_changeset_uuid=ae82bf3d-6282-4cac-ab82-dbf8384b610d&amp;customize_autosaved=on">
											Privacy						</a>
									</li>
																				<li id="wp-admin-bar-my-account-settings-group-invites">
											<a class="ab-item" href="<?= home_url(); ?>/members/winston/settings/invites/?customize_changeset_uuid=ae82bf3d-6282-4cac-ab82-dbf8384b610d&amp;customize_autosaved=on">
												Group Invites							</a>
										</li>
														<li id="wp-admin-bar-my-account-settings-export">
										<a class="ab-item" href="<?= home_url(); ?>/members/winston/settings/export/?customize_changeset_uuid=ae82bf3d-6282-4cac-ab82-dbf8384b610d&amp;customize_autosaved=on">
											Export Data						</a>
									</li>
													</ul>
							</div>
						</li>
						<li id="wp-admin-bar-my-account-activity" class="mb-2 menupop parent">
							<a class="ab-item" aria-haspopup="true" href="<?= home_url(); ?>/members/winston/activity/?customize_changeset_uuid=ae82bf3d-6282-4cac-ab82-dbf8384b610d&amp;customize_autosaved=on">
								<i class="bb-icon-l bb-icon-activity"></i>
								<span class="wp-admin-bar-arrow" aria-hidden="true"></span>Timeline			</a>
							<div class="ab-sub-wrapper wrapper">
								<ul id="wp-admin-bar-my-account-activity-default" class="ab-submenu">
									<li id="wp-admin-bar-my-account-activity-personal">
										<a class="ab-item" href="<?= home_url(); ?>/members/winston/activity/?customize_changeset_uuid=ae82bf3d-6282-4cac-ab82-dbf8384b610d&amp;customize_autosaved=on">Posts</a>
									</li>
													</ul>
							</div>
						</li>
						<!-- <li id="wp-admin-bar-my-account-notifications" class="mb-2 menupop parent">
							<a class="ab-item" aria-haspopup="true" href="<?= home_url(); ?>/members/winston/notifications/?customize_changeset_uuid=ae82bf3d-6282-4cac-ab82-dbf8384b610d&amp;customize_autosaved=on">
								<i class="bb-icon-l bb-icon-bell"></i>
								<span class="wp-admin-bar-arrow" aria-hidden="true"></span>Notifications			</a>
							<div class="ab-sub-wrapper wrapper">
								<ul id="wp-admin-bar-my-account-notifications-default" class="ab-submenu">
									<li id="wp-admin-bar-my-account-notifications-unread">
										<a class="ab-item" href="<?= home_url(); ?>/members/winston/notifications/?customize_changeset_uuid=ae82bf3d-6282-4cac-ab82-dbf8384b610d&amp;customize_autosaved=on">Unread</a>
									</li>
									<li id="wp-admin-bar-my-account-notifications-read">
										<a class="ab-item" href="<?= home_url(); ?>/members/winston/notifications/read/?customize_changeset_uuid=ae82bf3d-6282-4cac-ab82-dbf8384b610d&amp;customize_autosaved=on">Read</a>
									</li>
								</ul>
							</div>
						</li> -->
						<li id="wp-admin-bar-my-account-messages" class="mb-2 menupop parent wp-admin-bar-my-account-messages-1">
							<a class="ab-item" aria-haspopup="true" href="<?= home_url(); ?>/members/winston/messages/?customize_changeset_uuid=ae82bf3d-6282-4cac-ab82-dbf8384b610d&amp;customize_autosaved=on">
								<i class="bb-icon-l bb-icon-inbox"></i>
								<span class="wp-admin-bar-arrow" aria-hidden="true"></span>Messages			</a>
							<div class="ab-sub-wrapper wrapper">
								<ul id="wp-admin-bar-my-account-messages-default" class="ab-submenu">
									<li id="wp-admin-bar-my-account-messages-inbox">
										<a class="ab-item" href="<?= home_url(); ?>/members/winston/messages/?customize_changeset_uuid=ae82bf3d-6282-4cac-ab82-dbf8384b610d&amp;customize_autosaved=on">Messages</a>
									</li>
									<li id="wp-admin-bar-my-account-messages-compose">
										<a class="ab-item" href="<?= home_url(); ?>/members/winston/messages/compose/?customize_changeset_uuid=ae82bf3d-6282-4cac-ab82-dbf8384b610d&amp;customize_autosaved=on">New Message</a>
									</li>
															<li id="wp-admin-bar-my-account-messages-notices">
											<a class="ab-item" href="<?= home_url(); ?>/wp-admin/admin.php?page=bp-notices">Site Notices</a>
										</li>
													</ul>
							</div>
						</li>
						<li id="wp-admin-bar-my-account-friends" class="mb-2 menupop parent">
							<a class="ab-item" aria-haspopup="true" href="<?= home_url(); ?>/members/winston/friends/?customize_changeset_uuid=ae82bf3d-6282-4cac-ab82-dbf8384b610d&amp;customize_autosaved=on">
								<i class="bb-icon-l bb-icon-user-friends"></i>
								<span class="wp-admin-bar-arrow" aria-hidden="true"></span>Connections			</a>
							<div class="ab-sub-wrapper wrapper">
								<ul id="wp-admin-bar-my-account-friends-default" class="ab-submenu">
									<li id="wp-admin-bar-my-account-friends-friendships">
										<a class="ab-item" href="<?= home_url(); ?>/members/winston/friends/?customize_changeset_uuid=ae82bf3d-6282-4cac-ab82-dbf8384b610d&amp;customize_autosaved=on">My Connections</a>
									</li>
									<li id="wp-admin-bar-my-account-friends-requests">
										<a class="ab-item" href="<?= home_url(); ?>/members/winston/friends/requests/?customize_changeset_uuid=ae82bf3d-6282-4cac-ab82-dbf8384b610d&amp;customize_autosaved=on">No Pending Requests</a>
									</li>
								</ul>
							</div>
						</li>
						<li id="wp-admin-bar-my-account-groups" class="mb-2 menupop parent">
							<a class="ab-item" aria-haspopup="true" href="<?= home_url(); ?>/members/winston/groups/?customize_changeset_uuid=ae82bf3d-6282-4cac-ab82-dbf8384b610d&amp;customize_autosaved=on">
								<i class="bb-icon-l bb-icon-users"></i>
								<span class="wp-admin-bar-arrow" aria-hidden="true"></span>Studios			</a>
							<div class="ab-sub-wrapper wrapper">
								<ul id="wp-admin-bar-my-account-groups-default" class="ab-submenu">
									<li id="wp-admin-bar-my-account-groups-memberships">
										<a class="ab-item" href="<?= home_url(); ?>/members/winston/groups/?customize_changeset_uuid=ae82bf3d-6282-4cac-ab82-dbf8384b610d&amp;customize_autosaved=on">My Groups</a>
									</li>
									<li id="wp-admin-bar-my-account-groups-invites">
										<a class="ab-item" href="<?= home_url(); ?>/members/winston/groups/invites/?customize_changeset_uuid=ae82bf3d-6282-4cac-ab82-dbf8384b610d&amp;customize_autosaved=on">No Pending Invites</a>
									</li>
															<li id="wp-admin-bar-my-account-groups-create">
											<a class="ab-item" href="<?= home_url(); ?>/groups/create/?customize_changeset_uuid=ae82bf3d-6282-4cac-ab82-dbf8384b610d&amp;customize_autosaved=on">Create Group</a>
										</li>
													</ul>
							</div>
						</li>
						<li class="logout-link">
							<a href="<?= home_url(); ?>/wp-login.php?action=logout&amp;redirect_to=http%3A%2F%2Fcourscribe.local%2F%3Fcustomize_changeset_uuid%3Dae82bf3d-6282-4cac-ab82-dbf8384b610d%26customize_autosaved%3Don&amp;_wpnonce=627bd75408">
								<i class="bb-icon-l bb-icon-sign-out"></i>
								Log Out		
							</a>
						</li>
					</ul>
				</li>
				
				<!-- Other navbar items -->
			</ul>
		</div>
		
	</div>
</nav>


