<?php

namespace InfyOm\Generator\Generators\Scaffold;

use InfyOm\Generator\Common\CommandData;
use InfyOm\Generator\Generators\BaseGenerator;
use InfyOm\Generator\Utils\FileUtil;
use InfyOm\Generator\Utils\GeneratorFieldsInputUtil;

class ControllerGenerator extends BaseGenerator
{
    /** @var CommandData */
    private $commandData;

    /** @var string */
    private $path;

    /** @var string */
    private $templateType;

    /** @var string */
    private $fileName;

    public function __construct(CommandData $commandData)
    {
        $this->commandData = $commandData;
        $this->path = $commandData->config->pathController;
        $this->templateType = config('infyom.laravel_generator.templates', 'adminlte-templates');
        $this->fileName = $this->commandData->modelName.'Controller.php';
    }

    public function generate()
    {
	    $cc_model_name       = $this->commandData->dynamicVars["\$MODEL_NAME_CAMEL$"];
	    $relations           = $this->commandData->relations;
	    $store_relations     = [];
	    $modelRepositories   = [];
	    $modelRepoAttributes = [];
	    $varModelRepos       = [];
	    $constructModelRepos = [];
	    $getModelRepos       = [];
	    $getPolymorph        = [];
	    $sendModelRepos      = [];
	    $sendPolymorph       = [];
	    $polymorphOne        = '';
	    $fileStore           = [];
	    $fileCheck           = [];
	    $fileUpdate          = [];

	    $fieldsFile          = $this->commandData->config->options["fieldsFile"];
	    $fieldsFileLocation  = substr($fieldsFile, 0, strrpos($fieldsFile, "/") +1);


	    if ($this->commandData->getAddOn('datatables')){
	        $templateData = get_template('scaffold.controller.datatable_controller', 'laravel-generator');
	        $this->generateDataTable();
        } else {
	        $templateData = get_template('scaffold.controller.controller', 'laravel-generator');
	        $paginate = $this->commandData->getOption('paginate');
	        if ($paginate) {
	            $templateData = str_replace('$RENDER_TYPE$', 'paginate('.$paginate.')', $templateData);
            } else {
	            $templateData = str_replace('$RENDER_TYPE$', 'all()', $templateData);
            }
        }

	    foreach ($this->commandData->fields as $field)
	    {
	    	if($field->htmlType == 'file'){
			    $fileStore[] = "if(\$request->hasFile('".$field->name."')){".PHP_EOL.
				    "\$".$cc_model_name."->signature_file = \$request->".$field->name."->store('".$field->name."');".PHP_EOL.
				    "$".$cc_model_name."->save();".PHP_EOL.
			    "}".PHP_EOL;
			    $fileCheck[] = 'if(!empty($'.$cc_model_name.'->'.$field->name.')){'.PHP_EOL.
			                    '$previousFile = $'.$cc_model_name.'->'.$field->name.';'.PHP_EOL.
				                '}'.PHP_EOL;
			    $fileUpdate[] = 'if($request->hasFile("'.$field->name.'")){'.PHP_EOL.
				                '$fileData = pathinfo($request->file("'.$field->name.'")->getClientOriginalName());'.PHP_EOL.
			                    '$fileName = $fileData[\'filename\'] . "_". str_random(3) . time() . \'.\' . $fileData[\'extension\'];'.PHP_EOL.
				                '$'.$cc_model_name.'->'.$field->name.' = $request->'.$field->name.'->storeAs(\''.$field->name.'\', $fileName);'.PHP_EOL.
				                'Storage::delete($previousFile);'.PHP_EOL.
				                '$'.$cc_model_name.'->save();'.PHP_EOL.
				                '}'.PHP_EOL;
		    }
	    }



	    foreach ($relations as $relation)
	    {
		    if(!$relation->inputs[0] == '')
		    {
		    	$relation_name      = $relation->inputs[0];
			    $cc_relation        = camel_case($relation_name);
			    $plural_cc_relation = str_plural($cc_relation);
			    $relationFieldsFile = $fieldsFileLocation . $relation_name . '.json';
			    $relatedFields      = $this->getDataFromFieldsFile($relationFieldsFile);
			    $relationType       = $relation->type;

			    $modelRepoAttributes[] = 'private $'.$cc_relation.'Repository;';
			    $constructModelRepos[] = '$this->'.$cc_relation.'Repository = $'.$cc_relation.'Repo;';
			    $modelRepositories[]   = 'use App\Repositories\\'.$relation_name.'Repository;';
			    $varModelRepos[]       = ', '.$relation_name.'Repository $'.  $cc_relation. 'Repo';

			    switch ($relationType)
			    {
				    case '1t1':
					    break;
				    case '1tm':
				    case 'hmt':
				        $store_relations[] = 'if($request->input(\''.$cc_relation.'\') != null){'.'$'.$cc_model_name."->".$plural_cc_relation."()->createMany(\$request->input('.$cc_relation.'));}";
				        $getModelRepos[]       ='$'.$plural_cc_relation.' = $this->'.$cc_relation.'Repository->all();';
				        $sendModelRepos[]      = "->with('".$plural_cc_relation."', \$".$plural_cc_relation.")";
				        break;
				    case 'pm1':
					    $getPolymorph[]  =  '$'.$cc_relation.' = $'.$cc_model_name.'->'.$cc_relation.';';
					    $sendPolymorph[] = "->with('".$cc_relation."', \$".$cc_relation.")";
					    $polymorphOne    = '$'.$cc_model_name.'->'.$cc_relation.'()->create();';
					    break;
				    case 'mt1':
				    case 'mtm':
				    case 'pmm':
				        $getModelRepos[]       ='$'.$plural_cc_relation.' = $this->'.$cc_relation.'Repository->all();';
				        $sendModelRepos[]      = "->with('".$plural_cc_relation."', \$".$plural_cc_relation.")";
					    break;
				    default:
					    break;
			    }

			    foreach ($relatedFields as $field)
			    {
				    if((!empty($field->htmlValues[0])) && (strstr($field->htmlValues[0], 'foreign')) && ($field->htmlType == 'select'))
				    {
					    $foreign_array           = explode(':', $field->htmlValues[0]);
					    $foreign_model           = $foreign_array[1];
					    $cc_foreign_model        = camel_case($foreign_model);
					    $plural_cc_foreign_model = str_plural($cc_foreign_model);
					    $getModelRepos[]         = '$'.$plural_cc_foreign_model. ' = $this->'.$cc_foreign_model.'Repository->all();';
					    $sendModelRepos[]        = "->with('".$plural_cc_foreign_model."', $".$plural_cc_foreign_model.")";


					    if(!strstr($field->htmlValues[0], $this->commandData->config->mName)){
						    $modelRepositories[]   = 'use App\Repositories\\'.$foreign_model.'Repository;';
						    $modelRepoAttributes[] = 'private $'.$cc_foreign_model.'Repository;';
						    $varModelRepos[]       = ', '.$foreign_model.'Repository $'.$cc_foreign_model.'Repo';
						    $constructModelRepos[] = '$this->'.$cc_foreign_model.'Repository = $'.$cc_foreign_model.'Repo;';
					    }
				    }
				    if($field->htmlType == 'file'){

				    }
			    }
		    }
	    }

	    $templateData = str_replace('$MODEL_REPOSITORIES$', implode("\n", $modelRepositories), $templateData);
	    $templateData = str_replace('$MODEL_REPO_ATTRIBUTES$', implode("\n", $modelRepoAttributes), $templateData);
	    $templateData = str_replace('$VAR_MODEL_REPOS$', implode('', $varModelRepos), $templateData);
	    $templateData = str_replace('$CONSTRUCT_MODEL_REPOS$', implode("\n", $constructModelRepos), $templateData);
	    $templateData = str_replace('$GET_MODEL_REPOS$', implode("\n", $getModelRepos), $templateData);
	    $templateData = str_replace('$SEND_MODEL_REPOS$', implode('', $sendModelRepos), $templateData);
	    $templateData = str_replace('$GET_POLYMORPHS$', implode("\n", $getPolymorph), $templateData);
	    $templateData = str_replace('$SEND_POLYMORPHS$', implode('', $sendPolymorph), $templateData);
	    $templateData = str_replace('$STORE_RELATIONS$', implode("\n\n", $store_relations), $templateData);
	    $templateData = str_replace('$MORPH_ONE$', $polymorphOne, $templateData);
	    $templateData = str_replace('$FILE_STORE$', implode("\n", $fileStore), $templateData);
	    $templateData = str_replace('$FILE_CHECK$', implode("\n", $fileCheck), $templateData);
	    $templateData = str_replace('$FILE_UPDATE$', implode("\n", $fileUpdate), $templateData);

	    $templateData = fill_template($this->commandData->dynamicVars, $templateData);

	    FileUtil::createFile($this->path, $this->fileName, $templateData);

	    $this->commandData->commandComment("\nController created: ");
        $this->commandData->commandInfo($this->fileName);
    }

    private function generateDataTable()
    {
        $templateData = get_template('scaffold.datatable', 'laravel-generator');

        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        $headerFieldTemplate = get_template('scaffold.views.datatable_column', $this->templateType);

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

        $path = $this->commandData->config->pathDataTables;

        $fileName = $this->commandData->modelName.'DataTable.php';

        $fields = implode(','.infy_nl_tab(1, 3), $headerFields);

        $templateData = str_replace('$DATATABLE_COLUMNS$', $fields, $templateData);

        FileUtil::createFile($path, $fileName, $templateData);

        $this->commandData->commandComment("\nDataTable created: ");
        $this->commandData->commandInfo($fileName);
    }

    public function rollback()
    {
        if ($this->rollbackFile($this->path, $this->fileName)) {
            $this->commandData->commandComment('Controller file deleted: '.$this->fileName);
        }

        if ($this->commandData->getAddOn('datatables')) {
            if ($this->rollbackFile($this->commandData->config->pathDataTables, $this->commandData->modelName.'DataTable.php')) {
                $this->commandData->commandComment('DataTable file deleted: '.$this->fileName);
            }
        }
    }
}
