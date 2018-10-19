<?php

namespace InfyOm\Generator\Utils;

use function GuzzleHttp\Psr7\str;
use InfyOm\Generator\Common\GeneratorField;

class HTMLFieldGenerator
{
    public static function generateHTML(GeneratorField $field, $templateType)
    {
        $fieldTemplate = '';

        switch ($field->htmlType) {
            case 'text':
            case 'textarea':
            case 'date':
            case 'file':
            case 'email':
            case 'password':
                $fieldTemplate = get_template('scaffold.fields.'.$field->htmlType, $templateType);
                break;
            case 'number':
                $fieldTemplate = get_template('scaffold.fields.'.$field->htmlType, $templateType);
                break;
            case 'select':
            case 'enum':
                $fieldTemplate = get_template('scaffold.fields.select', $templateType);
                $radioLabels = GeneratorFieldsInputUtil::prepareKeyValueArrFromLabelValueStr($field->htmlValues);
		        if(array_key_exists('foreign',$radioLabels)){
			        $fieldTemplate = get_template('scaffold.fields.select_foreign', $templateType);
			        $fieldTemplate = str_replace('$FOREIGN$', camel_case($radioLabels['foreign']), $fieldTemplate);
			        break;
		        }
                $fieldTemplate = str_replace(
                    '$INPUT_ARR$',
                    GeneratorFieldsInputUtil::prepareKeyValueArrayStr($radioLabels),
                    $fieldTemplate
                );
                break;
            case 'checkbox':
                $fieldTemplate = get_template('scaffold.fields.checkbox', $templateType);
                if (count($field->htmlValues) > 0) {
                    $checkboxValue = $field->htmlValues[0];
                } else {
                    $checkboxValue = 1;
                }
                $fieldTemplate = str_replace('$CHECKBOX_VALUE$', $checkboxValue, $fieldTemplate);
                break;
            case 'radio':
                $fieldTemplate = get_template('scaffold.fields.radio_group', $templateType);
                $radioTemplate = get_template('scaffold.fields.radio', $templateType);

                $radioLabels = GeneratorFieldsInputUtil::prepareKeyValueArrFromLabelValueStr($field->htmlValues);

                $radioButtons = [];
                foreach ($radioLabels as $label => $value) {
                    $radioButtonTemplate = str_replace('$LABEL$', $label, $radioTemplate);
                    $radioButtonTemplate = str_replace('$VALUE$', $value, $radioButtonTemplate);
                    $radioButtons[] = $radioButtonTemplate;
                }
                $fieldTemplate = str_replace('$RADIO_BUTTONS$', implode("\n", $radioButtons), $fieldTemplate);
                break;
	        case 'hidden':
	        	$fieldTemplate = get_template('scaffold.fields.'.$field->htmlType, $templateType);
	        	break;
        }

        if(str_contains($field->migrationText, 'nullable')){
	        $fieldTemplate = str_replace('$REQUIRED$', '', $fieldTemplate);
        } else {
	        if((str_contains($fieldTemplate, '[') && (str_contains($fieldTemplate, 'Form::')))){
		        $fieldTemplate = str_replace('$REQUIRED$', ", 'required' => 'required' ", $fieldTemplate);
	        } elseif ((str_contains($fieldTemplate, 'Form::') && (!str_contains($fieldTemplate, '[')))) {
		        $fieldTemplate = str_replace('$REQUIRED$', " ['required' => 'required']", $fieldTemplate);
	        } else {
		        $fieldTemplate = str_replace('$REQUIRED$', ' required="required" ', $fieldTemplate);
	        }
        }

        return $fieldTemplate;
    }

	public static function generateJavascript(GeneratorField $field, $templateType)
	{
		$fieldTemplate = '';

		switch ($field->htmlType) {
			case 'text':
			case 'textarea':
			case 'date':
			case 'file':
			case 'email':
			case 'password':
				$fieldTemplate = get_template('scaffold.fields.'.$field->htmlType. '_js', $templateType);
				break;
			case 'number':
				$fieldTemplate = get_template('scaffold.fields.'.$field->htmlType.'_js', $templateType);
				break;
			case 'select':
			case 'enum':

				$fieldTemplate = get_template('scaffold.fields.select'.'_js', $templateType);
				$radioLabels = GeneratorFieldsInputUtil::prepareKeyValueArrFromLabelValueStr($field->htmlValues);
				if(array_key_exists('foreign',$radioLabels)){
					$fieldTemplate = get_template('scaffold.fields.select'.'_js', $templateType);
					$fieldTemplate = str_replace('$FOREIGN$', camel_case($radioLabels['foreign']), $fieldTemplate);
				} else {
					$fieldTemplate = str_replace(
						'$INPUT_ARR$',
						GeneratorFieldsInputUtil::prepareKeyValueArrayStr($radioLabels),
						$fieldTemplate
					);
				}
				break;
			case 'checkbox':
				$fieldTemplate = get_template('scaffold.fields.checkbox'.'_js', $templateType);
				if (count($field->htmlValues) > 0) {
					$checkboxValue = $field->htmlValues[0];
				} else {
					$checkboxValue = 1;
				}
				$fieldTemplate = str_replace('$CHECKBOX_VALUE$', $checkboxValue, $fieldTemplate);
				break;
			case 'radio':
				$fieldTemplate = get_template('scaffold.fields.radio_group'.'_js', $templateType);
				$radioTemplate = get_template('scaffold.fields.radio'.'_js', $templateType);

				$radioLabels = GeneratorFieldsInputUtil::prepareKeyValueArrFromLabelValueStr($field->htmlValues);

				$radioButtons = [];
				foreach ($radioLabels as $label => $value) {
					$radioButtonTemplate = str_replace('$LABEL$', $label, $radioTemplate);
					$radioButtonTemplate = str_replace('$VALUE$', $value, $radioButtonTemplate);
					$radioButtons[] = $radioButtonTemplate;
				}
				$fieldTemplate = str_replace('$RADIO_BUTTONS$', implode("\n", $radioButtons), $fieldTemplate);
				break;
		}

		if(str_contains($field->migrationText, 'nullable')){
			$fieldTemplate = str_replace('$REQUIRED$', '', $fieldTemplate);
		} else {
			if((str_contains($fieldTemplate, '[') && (str_contains($fieldTemplate, 'Form::')))){
				$fieldTemplate = str_replace('$REQUIRED$', ", 'required' => 'required' ", $fieldTemplate);
			} elseif ((str_contains($fieldTemplate, 'Form::') && (!str_contains($fieldTemplate, '[')))) {
				$fieldTemplate = str_replace('$REQUIRED$', " ['required' => 'required']", $fieldTemplate);
			} else {
				$fieldTemplate = str_replace('$REQUIRED$', ' required="required" ', $fieldTemplate);
			}
		}

		return $fieldTemplate;
	}

	public static function generateEditJavascript(GeneratorField $field, $templateType)
	{
		$fieldTemplate = '';

		switch ($field->htmlType) {
			case 'text':
			case 'textarea':
			case 'date':
			case 'file':
			case 'email':
			case 'password':
				$fieldTemplate = get_template('scaffold.fields.'.$field->htmlType. '_edit', $templateType);
				break;
			case 'number':
				$fieldTemplate = get_template('scaffold.fields.'.$field->htmlType.'_edit', $templateType);
				break;
			case 'select':
			case 'enum':

				$fieldTemplate = get_template('scaffold.fields.select'.'_edit', $templateType);
				$radioLabels = GeneratorFieldsInputUtil::prepareKeyValueArrFromLabelValueStr($field->htmlValues);
				if(array_key_exists('foreign',$radioLabels)){
					$fieldTemplate = get_template('scaffold.fields.select'.'_foreign_edit', $templateType);
					$fieldTemplate = str_replace('$FOREIGN$', camel_case($radioLabels['foreign']), $fieldTemplate);
				} else {
					$fieldTemplate = str_replace(
						'$INPUT_ARR$',
						GeneratorFieldsInputUtil::prepareKeyValueArrayStr($radioLabels),
						$fieldTemplate
					);
				}
				break;
			case 'checkbox':
				$fieldTemplate = get_template('scaffold.fields.checkbox'.'_edit', $templateType);
				if (count($field->htmlValues) > 0) {
					$checkboxValue = $field->htmlValues[0];
				} else {
					$checkboxValue = 1;
				}
				$fieldTemplate = str_replace('$CHECKBOX_VALUE$', $checkboxValue, $fieldTemplate);
				break;
			case 'radio':
				$fieldTemplate = get_template('scaffold.fields.radio_group'.'_edit', $templateType);
				$radioTemplate = get_template('scaffold.fields.radio'.'_edit', $templateType);

				$radioLabels = GeneratorFieldsInputUtil::prepareKeyValueArrFromLabelValueStr($field->htmlValues);

				$radioButtons = [];
				foreach ($radioLabels as $label => $value) {
					$radioButtonTemplate = str_replace('$LABEL$', $label, $radioTemplate);
					$radioButtonTemplate = str_replace('$VALUE$', $value, $radioButtonTemplate);
					$radioButtons[] = $radioButtonTemplate;
				}
				$fieldTemplate = str_replace('$RADIO_BUTTONS$', implode("\n", $radioButtons), $fieldTemplate);
				break;
		}

		if(str_contains($field->migrationText, 'nullable')){
			$fieldTemplate = str_replace('$REQUIRED$', '', $fieldTemplate);
		} else {
			if((str_contains($fieldTemplate, '[') && (str_contains($fieldTemplate, 'Form::')))){
				$fieldTemplate = str_replace('$REQUIRED$', ", 'required' => 'required' ", $fieldTemplate);
			} elseif ((str_contains($fieldTemplate, 'Form::') && (!str_contains($fieldTemplate, '[')))) {
				$fieldTemplate = str_replace('$REQUIRED$', " ['required' => 'required']", $fieldTemplate);
			} else {
				$fieldTemplate = str_replace('$REQUIRED$', ' required="required" ', $fieldTemplate);
			}
		}

		return $fieldTemplate;
	}
}
