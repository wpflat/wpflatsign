<?php
namespace BizyDoc;

use BizyCore\Services\Demo as Core_Demo;

defined( 'ABSPATH' ) || exit;

/** BizyDoc' part of the sample data: a template and a draft agreement ready to send. */
class Demo {

	public static function init() {
		add_action( 'bizycore_demo_load', array( __CLASS__, 'load' ), 30 );
	}

	public static function load( $ctx ) {
		if ( ! \BizyCore\Registry::enabled( 'doc' ) ) {
			return;
		}
		$body = '<h2>Service agreement</h2><p>This agreement is between {{company}} and {{client_name}}, dated {{date}}.</p><p>We will deliver the work described in the project brief, and {{client_name}} will pay the invoices issued for it.</p>';
		$tp   = Core_Demo::add( 'bizydoc_templates', array( 'name' => 'Service agreement (Sample)', 'description' => 'A short agreement to adapt', 'doc_type' => 'agreement', 'body' => $body, 'is_active' => 1, 'department_id' => $ctx['web'] ) );
		$num  = Services\Contracts::next_number();
		$text = Services\Contracts::merge( $body, array( 'client_id' => $ctx['clients'][0], 'title' => 'Website redesign agreement', 'number' => $num ) );
		Core_Demo::add( 'bizydoc_contracts', array( 'number' => $num, 'title' => 'Website redesign agreement (Sample)', 'client_id' => $ctx['clients'][0], 'template_id' => $tp, 'doc_type' => 'agreement', 'department_id' => $ctx['web'], 'status' => 'draft', 'body' => $text, 'view_access' => 'private' ) );
	}
}
