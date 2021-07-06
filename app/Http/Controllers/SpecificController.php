<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;



class SpecificController extends Controller
{
    protected $typesName = array(
        "N" => "int",
        "R" => "float",
        "char*" => "String",
        "Z" => "int",
        "B" => "bool"
    );

    protected $initValue = array(
        "bool" => "true",
        "int" => "0",
        "float" => "0",
        "String" => "\"\""
    );

    protected $condition = "";
    protected $post = "";
    protected $fName = "";
    protected $rsName = "";
    protected $rsType = "";
    protected $tmp = array();
    protected $lstVariables = array();

    protected $inputText = "";

    public function handle(Request $request)
    {

        $this->inputText = "SoLonHon(a:R,b:R)c:R
        pre 
        post ((c=a)&&(a>=b))||((c=b)&&(b>a))";

        $lines = explode("\n", $this->inputText);

        $this->getVariableInfo($lines[0]);
        //$this->lstVariables


        return response()->json([
            "code" => 200,
            "success" => true,
            "data" =>  $this->lstVariables
        ], 200);

        return eval('
        return response()->json([
            "code" => 200,
            "success" => true,
            "data" =>  $this->lstVariables
        ], 200);
        ');
    }

    public function getVariableInfo($line)
    {
        $start = strpos($line, '(');
        $end = strpos($line, ')');

        $variableString = substr($line, $start + 1, $end - $start - 1);

        $variables = explode(",", $variableString);

        foreach ($variables as $v) {
            $splits = explode(":", $v);
            $vName = trim($splits[0]);
            $vType = $this->typesName[trim($splits[1])];
            $Result = $vName." : ".$vType;

            array_push(
                $this->lstVariables,
                [
                    $vName => $vType
                ]
            );
        }
    }

    private function getResult($line)
    {
        $start = strpos($line,')');
        $resultString = substr($line,$start + 1);
        $rsName = trim(explode(':',$resultString)[0]);
        $rsType = $this->typesName[explode(':',$resultString)[1]];
    }
}
