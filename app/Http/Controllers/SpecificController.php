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

        $this->getResult($lines[0]);
        // $this->rsName
        // $this->rsType

        $this->getFunctionName($lines[0]);
        // $this->fName

        $this->getCondition($lines[1]);
        // $this->condition

        $this->getPost($lines[2]);
        // $this->post

        $this->getMainFunction($lines[2]);
        // $this->tmp

        return response()->json([
            "code" => 200,
            "success" => true,
            "data" =>  $this->tmp
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
            $Result = $vName . " : " . $vType;

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
        $start = strpos($line, ')');
        $resultString = substr($line, $start + 1);
        $this->rsName = trim(explode(':', $resultString)[0]);
        $this->rsType = $this->typesName[explode(':', $resultString)[1]];
    }

    private function getFunctionName($line)
    {
        $end = strpos($line, '(');
        $this->fName = trim(substr($line, 0, $end));
    }

    private function getCondition($line)
    {
        $this->condition = trim(substr($line, 0, 3));
    }


    private function getPost($line)
    {
        $this->post = trim(substr($line, 0, 4));
    }

    private function  getMainFunction($line)
    {
        $openCount = 0;
        $closeCount = 0;
        $cond = "";
        for ($i = 0; $i < strlen($line); $i++) {
            if ($line[$i] != '|' && $line[$i] != '&') {
                $cond = $cond.$line[$i];
                if ($line[$i] == '(')
                    $openCount++;
                if ($line[$i] == ')')
                    $closeCount++;
            } else {
                if ($openCount == $closeCount) {
                    $openCount = 0;
                    $closeCount = 0;
                    $i++;
                    $cond = trim($cond);
                    if ($cond[0] == '(' && $cond[strlen($cond) - 1] == ')')
                        $cond = trim(substr($cond, 1, strlen($cond) - 2));
                    array_push(
                        $this->tmp,
                        $cond
                    );
                    $cond = "";
                } else {
                    $cond = $cond.$line[$i];
                }
            }
            if ($i == strlen($line) - 1) {
                $cond = trim($cond);
                if ($cond[0] == '(' && $cond[strlen($cond) - 1] == ')')
                    $cond = substr($cond, 1, trim(strlen($cond) - 2));


                array_push(
                    $this->tmp,
                    $cond
                );
            }
        }
        for ($i = 0; $i < count($this->tmp); $i++) {
            // Console.WriteLine(tmp[i]);
        }
    }



    // String createInputFunction()
    // {
    //     String fInputRef = "";
    //     String fInputCode = "";
    //     foreach (var v in lstVariables)
    //     {
    //         String variable = "ref " + v.Value + " " + v.Key + ",";
    //         fInputRef += variable;
    //         fInputCode += $"\n\t\t\tConsole.WriteLine(\"Nhap {v.Key}: \");";
    //         if (v.Value != "String")
    //             fInputCode += $"\n\t\t\t{v.Key} = {v.Value}.Parse(Console.ReadLine());";
    //         else
    //             fInputCode += "\n\t\t\tConsole.ReadLine();";
    //     }
    //     fInputRef = fInputRef.Substring(0, fInputRef.Length - 1);
    //     return $"\t\tpublic void Nhap_{fName}({fInputRef})\n\t\t{{{fInputCode}\n\t\t}}\n";
    // }

    // String createOutputFunction()
    // {
    //     String fInputRef = rsType + " " + rsName;
    //     String fOutputCode = $"\n\t\t\tConsole.WriteLine(\"Ket qua la: {{0}}\", {rsName});\n";
    //     return $"\t\tpublic void Xuat_{fName}({fInputRef})\n\t\t{{{fOutputCode}\n\t\t}}\n";
    // }

}
