<?php

namespace Wpae\Pro\Filtering;

/**
 * Class FilteringGuestCustomers
 * @package Wpae\Pro\Filtering
 */
class FilteringGuestCustomers extends FilteringBase
{
    /**
     * @return bool
     */
    public function parse(){
        if ( $this->isFilteringAllowed()){
            $this->checkNewStuff();

            // Always start with guest customer base condition
            $base_condition = " AND user_id IS NULL";

            // Add "customers that made purchases" filter if enabled
            if (!empty(\XmlExportEngine::$exportOptions['export_only_customers_that_made_purchases'])) {
                $base_condition .= " AND order_count > 0";
            }

            // No additional filtering rules defined
            if ( empty($this->filterRules)) {
                $this->queryWhere .= $base_condition;
                return FALSE;
            }

            $this->queryWhere = $this->isExportNewStuff() ? $this->queryWhere . " AND (" : " AND (";

            // Apply Filtering Rules
            foreach ($this->filterRules as $rule) {
                if ( is_null($rule->parent_id) ) {
                    $this->parse_single_rule($rule);
                }
            }

            // Add base guest customer conditions
            $this->queryWhere .= $base_condition;

            if ($this->meta_query || $this->tax_query) {
                $this->queryWhere .= " ) GROUP BY customer_id";
            }
            else {
                $this->queryWhere .= ")";
            }
        }
        return TRUE;
    }

    /**
     * Check for new stuff (export only new records)
     */
    public function checkNewStuff(){
        //If re-run, this export will only include records that have not been previously exported.
        if ($this->isExportNewStuff()){
            $postList = new \PMXE_Post_List();
            $this->queryWhere = " AND (customer_id NOT IN (SELECT post_id FROM " . $postList->getTable() . " WHERE export_id = '". $this->exportId ."'))";
        }
    }

    /**
     * Parse a single filtering rule for guest customers
     * @param $rule
     */
    public function parse_single_rule($rule) {
        apply_filters('wp_all_export_single_filter_rule', $rule);

        switch($rule->element) {
            case 'customer_id':
                $this->queryWhere .= $this->build_condition('customer_id', $rule, true);
                break;
            case 'email':
                $this->queryWhere .= $this->build_condition('email', $rule, false);
                break;
            case 'first_name':
                $this->queryWhere .= $this->build_condition('first_name', $rule, false);
                break;
            case 'last_name':
                $this->queryWhere .= $this->build_condition('last_name', $rule, false);
                break;
            case 'order_count':
                $this->queryWhere .= $this->build_condition('order_count', $rule, true);
                break;
            case 'total_spend':
                $this->queryWhere .= $this->build_condition('total_spend', $rule, true);
                break;
            case 'avg_order_value':
                $this->queryWhere .= $this->build_condition('avg_order_value', $rule, true);
                break;
            case 'date_registered':
                $this->parse_date_field($rule);
                $this->queryWhere .= $this->build_condition('date_registered', $rule, false);
                break;
            case 'date_last_active':
                $this->parse_date_field($rule);
                $this->queryWhere .= $this->build_condition('date_last_active', $rule, false);
                break;
            case 'country':
                $this->queryWhere .= $this->build_condition('country', $rule, false);
                break;
            case 'state':
                $this->queryWhere .= $this->build_condition('state', $rule, false);
                break;
            case 'city':
                $this->queryWhere .= $this->build_condition('city', $rule, false);
                break;
            case 'postcode':
                $this->queryWhere .= $this->build_condition('postcode', $rule, false);
                break;
            default:
                // For unknown fields, try to handle as custom field
                if (strpos($rule->element, "cf_") === 0) {
                    // Custom fields for guest customers would need special handling
                    // For now, skip unknown custom fields
                }
                break;
        }
    }

    /**
     * Get exclude query where clause
     */
    protected function getExcludeQueryWhere($postsToExclude) {
        if (empty($postsToExclude)) {
            return '';
        }

        $exclude_ids = array_map('intval', $postsToExclude);
        return " AND customer_id NOT IN (" . implode(',', $exclude_ids) . ")";
    }

    /**
     * Get modified query where clause
     */
    protected function getModifiedQueryWhere($export) {
        // Guest customers don't have modification tracking like WordPress posts
        // Return empty string for now
        return '';
    }

    /**
     * Build complete condition including field name
     */
    protected function build_condition($field_name, $rule, $is_int = false) {
        // Handle special cases that need field name in the condition
        if ($rule->condition == 'is_empty') {
            if ($is_int) {
                return "({$field_name} IS NULL OR {$field_name} = 0)";
            } else {
                return "({$field_name} IS NULL OR {$field_name} = '')";
            }
        } elseif ($rule->condition == 'is_not_empty') {
            if ($is_int) {
                return "({$field_name} IS NOT NULL AND {$field_name} != 0)";
            } else {
                return "({$field_name} IS NOT NULL AND {$field_name} != '')";
            }
        } else {
            // For all other conditions, use the standard approach
            return $field_name . " " . $this->parse_condition($rule, $is_int);
        }
    }

    /**
     * Parse condition for guest customer fields
     */
    protected function parse_condition($rule, $is_int = false, $table_alias = false) {
        $value = $rule->value;

        switch($rule->condition) {
            case 'equals':
                if ($is_int) {
                    $value = is_numeric($value) ? intval($value) : 0;
                    return "= {$value}";
                } else {
                    return "= " . $this->wpdb->prepare('%s', $value);
                }
            case 'not_equals':
            case 'not_equal':
                if ($is_int) {
                    $value = is_numeric($value) ? intval($value) : 0;
                    return "!= {$value}";
                } else {
                    return "!= " . $this->wpdb->prepare('%s', $value);
                }
            case 'greater_than':
            case 'greater':
                if ($is_int) {
                    $value = is_numeric($value) ? intval($value) : 0;
                    return "> {$value}";
                } else {
                    return "> " . $this->wpdb->prepare('%s', $value);
                }
            case 'less_than':
            case 'less':
                if ($is_int) {
                    $value = is_numeric($value) ? intval($value) : 0;
                    return "< {$value}";
                } else {
                    return "< " . $this->wpdb->prepare('%s', $value);
                }
            case 'greater_than_or_equal':
            case 'equals_or_greater':
                if ($is_int) {
                    $value = is_numeric($value) ? intval($value) : 0;
                    return ">= {$value}";
                } else {
                    return ">= " . $this->wpdb->prepare('%s', $value);
                }
            case 'less_than_or_equal':
            case 'equals_or_less':
                if ($is_int) {
                    $value = is_numeric($value) ? intval($value) : 0;
                    return "<= {$value}";
                } else {
                    return "<= " . $this->wpdb->prepare('%s', $value);
                }
            case 'contains':
                return "LIKE " . $this->wpdb->prepare('%s', '%' . $value . '%');
            case 'not_contains':
                return "NOT LIKE " . $this->wpdb->prepare('%s', '%' . $value . '%');
            case 'starts_with':
                return "LIKE " . $this->wpdb->prepare('%s', $value . '%');
            case 'ends_with':
                return "LIKE " . $this->wpdb->prepare('%s', '%' . $value);
            case 'is_in_list':
                $values = array_map('trim', explode(',', $value));
                $values = array_map('intval', $values); // For guest customers, assume numeric IDs
                return "IN (" . implode(',', $values) . ")";
            case 'is_not_in_list':
                $values = array_map('trim', explode(',', $value));
                $values = array_map('intval', $values); // For guest customers, assume numeric IDs
                return "NOT IN (" . implode(',', $values) . ")";
            default:
                if ($is_int) {
                    $value = is_numeric($value) ? intval($value) : 0;
                    return "= {$value}";
                } else {
                    return "= " . $this->wpdb->prepare('%s', $value);
                }
        }
    }

    /**
     * Parse date field for guest customers
     */
    protected function parse_date_field(&$rule) {
        // Handle date formatting if needed
        // Guest customer dates are typically in MySQL datetime format
        if (!empty($rule->value) && !is_numeric($rule->value)) {
            $rule->value = date('Y-m-d H:i:s', strtotime($rule->value));
        }
    }
}