<?php

/**
 * @param $uid
 * @throws Exception
 */
function pmui_delete_user($uid) {
    if (class_exists('PMXI_Post_Record')) {
        $postList = new PMXI_Post_List();
        $args = array(
            'post_id' => $uid,
        );
        foreach($postList->getBy($args)->convertRecords() as $postRecord) {
            if (!$postRecord->isEmpty() && !empty($postRecord->import_id)) {
                $import = new PMXI_Import_Record();
                $import->getById($postRecord->import_id);
                if ( ! $import->isEmpty() ) {
                    if (!empty($import['options']['custom_type']) && in_array($import['options']['custom_type'], ['import_users', 'shop_customer'])) {
                        // Ensure that we're deleting a user relationship.
                        $user_data = get_userdata($uid);
                        if ( ! empty($user_data) ) {
                            $postRecord->delete();
                        }
                    }
                }
            }
        }
    }
}
