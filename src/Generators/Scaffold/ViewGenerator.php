<?php

namespace InfyOm\Generator\Generators\Scaffold;


use Illuminate\Support\Str;
use InfyOm\Generator\Common\CommandData;
use InfyOm\Generator\Common\GeneratorField;
use InfyOm\Generator\Common\GeneratorFieldRelation;
use InfyOm\Generator\Generators\BaseGenerator;
use InfyOm\Generator\Utils\FileUtil;
use InfyOm\Generator\Utils\GeneratorFieldsInputUtil;
use InfyOm\Generator\Utils\HTMLFieldGenerator;

class ViewGenerator extends BaseGenerator
{
    /** @var CommandData */
    private $commandData;

    /** @var string */
    private $path;

    /** @var string */
    private $templateType;

    /** @var array */
    private $htmlFields;

    public function __construct(CommandData $commandData)
    {
        $this->commandData = $commandData;
        $this->path = $commandData->config->pathViews;
        $this->templateType = config('infyom.laravel_generator.templates', 'adminlte-templates');
    }

    public function generate()
    {
        if (!file_exists($this->path)) {
            mkdir($this->path, 0755, true);
        }

        $this->commandData->commandComment("\nGenerating Views...");

        if ($this->commandData->getOption('views')) {
            $viewsToBeGenerated = explode(',', $this->commandData->getOption('views'));

            if (in_array('index', $viewsToBeGenerated)) {
                $this->generateTable();
                $this->generateIndex();
            }

            if (count(array_intersect(['create', 'update','fields'], $viewsToBeGenerated)) > 0) {
                $this->generateFields();
            }

            if (in_array('create', $viewsToBeGenerated)) {
                $this->generateCreate();
            }

            if (in_array('edit', $viewsToBeGenerated)) {
                $this->generateUpdate();
            }

            if (in_array('show', $viewsToBeGenerated)) {
                $this->generateShowFields();
                $this->generateShow();
            }

	        if (in_array('show_fields', $viewsToBeGenerated)) {
		        $this->generateShowFields();
	        }

	        if (in_array('relations', $viewsToBeGenerated)) {
		        $this->generateCreateRelations();
		        $this->generateEditRelations();
	        }
        } else {
            $this->generateTable();
            $this->generateIndex();
            $this->generateFields();
            $this->generateCreate();
            $this->generateUpdate();
            $this->generateShowFields();
            $this->generateShow();
	        $this->generateCreateRelations();
	        $this->generateEditRelations();
        }

        $this->commandData->commandComment('Views created: ');
    }

    private function generateTable()
    {
        if ($this->commandData->getAddOn('datatables')) {
            $templateData = $this->generateDataTableBody();
            $this->generateDataTableActions();
        } else {
            $templateData = $this->generateBladeTableBody();
        }

        FileUtil::createFile($this->path, 'table.blade.php', $templateData);

        $this->commandData->commandInfo('table.blade.php created');
    }

    private function generateDataTableBody()
    {
        $templateData = get_template('scaffold.views.datatable_body', $this->templateType);

        return fill_template($this->commandData->dynamicVars, $templateData);
    }

    private function generateDataTableActions()
    {
        $templateData = get_template('scaffold.views.datatables_actions', $this->templateType);

        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        FileUtil::createFile($this->path, 'datatables_actions.blade.php', $templateData);

        $this->commandData->commandInfo('datatables_actions.blade.php created');
    }

    private function generateBladeTableBody()
    {
        $templateData = get_template('scaffold.views.blade_table_body', $this->templateType);

        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        $templateData = str_replace('$FIELD_HEADERS$', $this->generateTableHeaderFields(), $templateData);

        $cellFieldTemplate = get_template('scaffold.views.table_cell', $this->templateType);

        $tableBodyFields = [];

        foreach ($this->commandData->fields as $field) {
            if (!$field->inIndex) {
                continue;
            }

            $tableBodyFields[] = fill_template_with_field_data(
                $this->commandData->dynamicVars,
                $this->commandData->fieldNamesMapping,
                $cellFieldTemplate,
                $field
            );
        }

        $tableBodyFields = implode(infy_nl_tab(1, 3), $tableBodyFields);

        return str_replace('$FIELD_BODY$', $tableBodyFields, $templateData);
    }

    private function generateTableHeaderFields()
    {
        $headerFieldTemplate = get_template('scaffold.views.table_header', $this->templateType);

        $headerFields = [];

        foreach ($this->commandData->fields as $field) {
            if (!$field->inIndex) {
                continue;
            }
            $headerFields[] = $fieldTemplate = fill_template_with_field_data(
                $this->commandData->dynamicVars,
                $this->commandData->fieldNamesMapping,
                $headerFieldTemplate,
                $field
            );
        }

        return implode(infy_nl_tab(1, 2), $headerFields);
    }

    private function generateIndex()
    {
        $templateData = get_template('scaffold.views.index', $this->templateType);

        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        if ($this->commandData->getOption('datatables')) {
            $templateData = str_replace('$PAGINATE$', '', $templateData);
        } else {
            $paginate = $this->commandData->getOption('paginate');

            if ($paginate) {
                $paginateTemplate = get_template('scaffold.views.paginate', $this->templateType);

                $paginateTemplate = fill_template($this->commandData->dynamicVars, $paginateTemplate);

                $templateData = str_replace('$PAGINATE$', $paginateTemplate, $templateData);
            } else {
                $templateData = str_replace('$PAGINATE$', '', $templateData);
            }
        }

        FileUtil::createFile($this->path, 'index.blade.php', $templateData);

        $this->commandData->commandInfo('index.blade.php created');
    }

    private function generateFields()
    {
        $this->htmlFields = [];

        foreach ($this->commandData->fields as $field) {
            if (!$field->inForm) {
                continue;
            }

            $fieldTemplate = HTMLFieldGenerator::generateHTML($field, $this->templateType);

            if (!empty($fieldTemplate)) {
                $fieldTemplate = fill_template_with_field_data(
                    $this->commandData->dynamicVars,
                    $this->commandData->fieldNamesMapping,
                    $fieldTemplate,
                    $field
                );
                $this->htmlFields[] = $fieldTemplate;
            }
        }

        $templateData = get_template('scaffold.views.fields', $this->templateType);
        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        $templateData = str_replace('$FIELDS$', implode("\n\n", $this->htmlFields), $templateData);
	    $templateData = str_replace('$THIS$', camel_case($this->commandData->modelName), $templateData);

        FileUtil::createFile($this->path, 'fields.blade.php', $templateData);
        $this->commandData->commandInfo('field.blade.php created');
    }

    private function generateCreate()
    {
        $templateData    = get_template('scaffold.views.create', $this->templateType);

	    $relationships   = $this->commandData->relations;

	    $relationsView   = [];
	    $relationsViewJs = [];

	    foreach ($relationships as $relation)
	    {
		    if(!$relation->inputs[0] == ''){

			    $relationType       = $relation->type;
			    $relationName       = snake_case($relation->inputs[0]);
			    $relationNameJs     = kebab_case($relation->inputs[0]);

			    switch ($relationType){
				    case '1t1':
					    break;
				    case '1tm':
					    $relationsView[]   = str_replace('relations',$relationName,"@include('\$VIEW_PREFIX\$\$MODEL_NAME_PLURAL_SNAKE\$.relations_create')");
					    $relationsViewJs[] = str_replace('relation', $relationNameJs, "@yield('javascript-relation')");
					    break;
				    case 'mt1':
					    break;
				    case 'mtm':
					    break;
				    case 'hmt':
					    $through           = str_plural(snake_case($relation->inputs[1]));
					    $relationsView[]   = str_replace('relations',$relationName,"@include('\$VIEW_PREFIX\$".$through.".relations_create')");
					    $relationsViewJs[] = str_replace('relation', $relationNameJs, "@yield('javascript-relation')");
					    break;
				    case 'pm1':
					    break;
				    case 'pmm':
					    break;
				    default:
					    break;

			    }
		    }
	    }

	    $templateData = str_replace('$RELATIONS$', implode("\n\n", $relationsView), $templateData);
	    $templateData = str_replace('$JSRELATIONS$', implode("\n\n", $relationsViewJs), $templateData);
	    $templateData = fill_template($this->commandData->dynamicVars, $templateData);
	    $templateData = str_replace('$THIS$', camel_case($this->commandData->modelName), $templateData);

        FileUtil::createFile($this->path, 'create.blade.php', $templateData);
        $this->commandData->commandInfo('create.blade.php created');
    }

    private function generateUpdate()
    {
        $templateData = get_template('scaffold.views.edit', $this->templateType);

	    $relationships = $this->commandData->relations;

	    $oneToManyRelationsView = [];
	    $manyToManyRelationsView = [];

	    foreach ($relationships as $relation)
	    {
		    if((!$relation->inputs[0] == ''))
		    {
			    $relationType       = $relation->type;
			    $relationName       = snake_case($relation->inputs[0]);

			    switch ($relationType){
				    case '1t1':
					    break;
				    case '1tm':
					    $oneToManyRelationsView[] = str_replace('relations',$relationName,"@include('\$VIEW_PREFIX\$\$MODEL_NAME_PLURAL_SNAKE\$.relations_edit')");
					    break;
				    case 'mt1':
					    break;
				    case 'mtm':
					    $manyToManyRelationsView[] = str_replace('relations',$relationName,"@include('\$VIEW_PREFIX\$\$MODEL_NAME_PLURAL_SNAKE\$.relations')");
					    break;
				    case 'hmt':
					    $through = str_plural(snake_case($relation->inputs[1]));
					    $oneToManyRelationsView[] = str_replace('relations',$relationName,"@include('\$VIEW_PREFIX\$".$through.".relations_edit')");
					    break;
				    case 'pm1':
					    break;
				    case 'pmm':
					    break;
				    default:
					    break;
			    }
		    }
	    }

	    $templateData = str_replace('$1TM_RELATIONS$', implode("\n\n", $oneToManyRelationsView), $templateData);
	    $templateData = str_replace('$MTM_RELATIONS$', implode("\n\n", $manyToManyRelationsView), $templateData);
	    $templateData = fill_template($this->commandData->dynamicVars, $templateData);
	    $templateData = str_replace('$THIS$', camel_case($this->commandData->modelName), $templateData);

        FileUtil::createFile($this->path, 'edit.blade.php', $templateData);
        $this->commandData->commandInfo('edit.blade.php created');
    }

    private function generateShowFields()
    {
        $fieldTemplate = get_template('scaffold.views.show_field', $this->templateType);

        $fieldsStr = '';

        foreach ($this->commandData->fields as $field) {
            $singleFieldStr = str_replace('$FIELD_NAME_TITLE$', Str::title(str_replace('_', ' ', $field->name)),
                $fieldTemplate);
            $singleFieldStr = str_replace('$FIELD_NAME$', $field->name, $singleFieldStr);
            $singleFieldStr = fill_template($this->commandData->dynamicVars, $singleFieldStr);

            $fieldsStr .= $singleFieldStr."\n\n";
        }

        FileUtil::createFile($this->path, 'show_fields.blade.php', $fieldsStr);
        $this->commandData->commandInfo('show_fields.blade.php created');
    }

    private function generateShow()
    {
        $templateData = get_template('scaffold.views.show', $this->templateType);

        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        FileUtil::createFile($this->path, 'show.blade.php', $templateData);
        $this->commandData->commandInfo('show.blade.php created');
    }

	private function generateCreateRelations()
	{
		list($relationships, $fieldsFileLocation) = $this->getFieldsFile();

		foreach ($relationships as $relation)
		{
			if(!$relation->inputs[0] == '')
			{

				$relationfieldsFile  = $fieldsFileLocation . $relation->inputs[0] . '.json';
				$relationFields      = $this->getDataFromFieldsFile($relationfieldsFile);

				$relationName       = $relation->inputs[0];
				$ccRelationName     = camel_case($relationName);
				$pluralRelationName = str_plural($ccRelationName);
				$titleRelationName  = str_plural(preg_replace('/(?<!\ )[A-Z]/', ' $0', $relationName));
				$relationType       = $relation->type;
				$filePrefix         = snake_case($relationName);
				$kebabRelationName  = kebab_case($relationName);

				$templateData = '';

				$htmlFields         = [];

				switch ($relationType) {
					case '1t1':
						break;
					case '1tm':
						$templateData = get_template('scaffold.views.relations_one_to_many_fields', $this->templateType);

						foreach ($relationFields as $relatedField)
						{
							if ($relatedField->inForm)
							{
								$fieldTemplate = HTMLFieldGenerator::generateJavascript($relatedField, $this->templateType);

								if (!empty($fieldTemplate))
								{
									$fieldTemplate = fill_template_with_field_data(
										$this->commandData->dynamicVars,
										$this->commandData->fieldNamesMapping,
										$fieldTemplate,
										$relatedField
									);
									$htmlFields[]  = $fieldTemplate;
								}
							}
						}

						$templateData = str_replace('$RELATIONJS$', $kebabRelationName, $templateData);
						$templateData = str_replace('$FIELDS$', implode("\n\n", $htmlFields), $templateData);
						$fileSuffix = '_create.blade.php';
						break;
					case 'mt1':
						break;
					case 'mtm':
						$templateData = get_template('scaffold.views.relations_many_to_many_fields', $this->templateType);

						$pivotTable = $relation->inputs[1];
						$pivotModel = rtrim(ucfirst(camel_case($pivotTable)), 's');
						$pivotJson  = $pivotModel . '.json';

						$pivotFields = $this->getDataFromFieldsFile($fieldsFileLocation . $pivotJson);

						foreach ($pivotFields as $pivotField)
						{
							if ($pivotField->inForm) {

								$fieldTemplate = HTMLFieldGenerator::generateHTML($pivotField, $this->templateType);

								if (!empty($fieldTemplate))
								{
									$fieldTemplate = fill_template_with_field_data(
										$this->commandData->dynamicVars,
										$this->commandData->fieldNamesMapping,
										$fieldTemplate,
										$pivotField
									);
									$htmlFields[]  = $fieldTemplate;
								}

								$templateData = str_replace('$FIELDS$', implode("\n\n", $htmlFields), $templateData);
							}

						}
						$templateData = str_replace(
							'$CHECKBOX$',
							'<div class="form-group col-sm-1"><input type="checkbox" value="{{$'. $ccRelationName . '->id}}" name="$'.$ccRelationName.'[ {{$'.$ccRelationName.'->name}}]"></div>',
							$templateData);
						$templateData = str_replace('$PCCRELATION$', $pluralRelationName, $templateData);
						$templateData = str_replace('$CCRELATION$', $ccRelationName, $templateData);

						$fileSuffix = '.blade.php';
						break;
					case 'hmt':
						break;
					case 'pm1':
						break;
					case 'pmm':
						break;
					default:
						break;
				}

				if ($templateData != '') {
					$templateData = str_replace('$RELATION_TITLE$', $titleRelationName, $templateData);
					$templateData = str_replace('$THIS$', camel_case($this->commandData->modelName), $templateData);

					$fileName = $filePrefix . $fileSuffix;

					$templateData = fill_template($this->commandData->dynamicVars, $templateData);

					FileUtil::createFile($this->path, $fileName, $templateData);

					$this->commandData->commandInfo($fileName);
				}

			}
		}


    }

	private function generateEditRelations()
	{
		list($relationships, $fieldsFileLocation) = $this->getFieldsFile();

		foreach ($relationships as $relation)
		{
			if ($relation->inputs[0] != '')
			{

				$relationName       = $relation->inputs[0];
				$ccRelationName     = camel_case($relationName);
				$pluralRelationName = str_plural($ccRelationName);
				$titleRelationName  = preg_replace('/(?<!\ )[A-Z]/', ' $0', $relationName);
				$relationType       = $relation->type;
				$filePrefix         = snake_case($relationName);
				$relationFieldsFileDir    = str_plural($filePrefix);

				$templateData = '';

				switch ($relationType) {
					case '1t1':
						break;
					case '1tm':
						$templateData = get_template('scaffold.views.relations_edit_one_to_many', $this->templateType);
						$templateData = str_replace('$SCFILEDIR$', $relationFieldsFileDir, $templateData);
						$fileSuffix = '_edit.blade.php';
						break;
					case 'mt1':
						break;
					case 'mtm':
						break;
					case 'hmt':
						break;
					case 'pm1':
						break;
					case 'pmm':
						break;
					default:
						break;
				}

				if($templateData != ''){

					$templateData = str_replace('$RELATION_TITLE$', $titleRelationName, $templateData);
					$templateData = str_replace('$PCCRELATION$', $pluralRelationName, $templateData);
					$templateData = str_replace('$CCRELATION$', $ccRelationName, $templateData);
					$templateData = str_replace('$RELATION_NAME$', $relationName, $templateData);
					$templateData = str_replace('$THIS$', camel_case($this->commandData->modelName), $templateData);

					$fileName = $filePrefix. $fileSuffix;

					$templateData = fill_template($this->commandData->dynamicVars, $templateData);

					FileUtil::createFile($this->path, $fileName, $templateData);

					$this->commandData->commandInfo($fileName);

				}

			}

		}



    }




    public function rollback()
    {
        $files = [
            'table.blade.php',
            'index.blade.php',
            'fields.blade.php',
            'create.blade.php',
            'edit.blade.php',
            'show.blade.php',
            'show_fields.blade.php',
        ];

        if ($this->commandData->getAddOn('datatables')) {
            $files[] = 'datatables_actions.blade.php';
        }

        foreach ($files as $file) {
            if ($this->rollbackFile($this->path, $file)) {
                $this->commandData->commandComment($file.' file deleted');
            }
        }
    }

	/**
	 * @return array
	 */
	private function getFieldsFile()
	{
		$relationships = $this->commandData->relations;

		$fieldsFile         = $this->commandData->config->options["fieldsFile"];
		$fieldsFileLocation = substr($fieldsFile, 0, strrpos($fieldsFile, "/") + 1);

		return array($relationships, $fieldsFileLocation);
	}
}
