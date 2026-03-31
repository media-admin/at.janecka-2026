<?php

function pmaep_init(){
	// Suppress "Pro required" notices in the free plugin's UI when the Pro plugin is active.
	add_filter('pmae_is_show_pro_notice_for_field', '__return_false');
}