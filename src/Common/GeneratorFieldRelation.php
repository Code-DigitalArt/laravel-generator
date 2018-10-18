<?php

namespace InfyOm\Generator\Common;

class GeneratorFieldRelation
{
    /** @var string */
    public $type;
    public $inputs;

    public static function parseRelation($relationInput)
    {
        $inputs = explode(',', $relationInput);

        $relation = new self();
        $relation->type = array_shift($inputs);
        $relation->inputs = $inputs;

        return $relation;
    }

    public function getRelationFunctionText()
    {
        $modelName = $this->inputs[0];
        switch ($this->type) {
            case '1t1':
                $functionName = camel_case($modelName);
                $relation = 'hasOne';
                $relationClass = 'HasOne';
                break;
            case '1tm':
                $functionName = camel_case(str_plural($modelName));
                $relation = 'hasMany';
                $relationClass = 'HasMany';
                break;
            case 'mt1':
                $functionName = camel_case($modelName);
                $relation = 'belongsTo';
                $relationClass = 'BelongsTo';
                break;
            case 'mtm':
                $functionName = camel_case(str_plural($modelName));
                $relation = 'belongsToMany';
                $relationClass = 'BelongsToMany';
                break;
            case 'hmt':
                $functionName = camel_case(str_plural($modelName));
                $relation = 'hasManyThrough';
                $relationClass = 'HasManyThrough';
                break;
	        case 'pm1':
		        $functionName = camel_case(str_plural($modelName));
		        $relation = 'morphOne';
		        $relationClass = 'MorphOne';
		        break;
	        case 'pmm':
		        $functionName = camel_case(str_plural($modelName));
		        $relation = 'morphMany';
		        $relationClass = 'MorphMany';
		        break;
	        case 'pm':
		        $functionName = camel_case($modelName) . 'able';
		        $relation = 'morphTo';
		        $relationClass = 'MorphTo';
		        break;
            default:
                $functionName = '';
                $relation = '';
                $relationClass = '';
                break;
        }

        if (!empty($functionName) and !empty($relation)) {
            return $this->generateRelation($functionName, $relation, $relationClass, $modelName);
        }

        return '';
    }

    private function generateRelation($functionName, $relation, $relationClass, $nameModel)
    {
        $inputs = $this->inputs;
        $modelName = array_shift($inputs);

        if($relation == 'morphTo'){
        	$template = get_template('model.relationship_polymorph', 'laravel-generator');
        } elseif (($relation == 'morphOne') || ($relation == 'morphMany')){
			$template = get_template('model.relationship_polymorphs', 'laravel-generator');
        } else{
	        $template = get_template('model.relationship', 'laravel-generator');
        }

	    $template = str_replace('$RELATIONSHIP_CLASS$', $relationClass, $template);
        $template = str_replace('$FUNCTION_NAME$', $functionName, $template);
        $template = str_replace('$RELATION$', $relation, $template);
        $template = str_replace('$RELATION_MODEL_NAME$', $modelName, $template);
        $template = str_replace('$MODEL_NAME$', camel_case($nameModel), $template);

        if (count($inputs) > 0) {
            $inputFields = implode("', '", $inputs);
            $inputFields = ", '".$inputFields."'";
        } else {
            $inputFields = '';
        }

        $template = str_replace('$INPUT_FIELDS$', $inputFields, $template);

        return $template;
    }
}
