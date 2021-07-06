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
        

        //createInputFunction()

        echo $this->createInputFunction();
        echo $this->createOutputFunction();
        echo $this-> createCheckFunction();
        echo $this-> createResultFunction();
        echo $this->createMainFunction();

        return response()->json([
            "code" => 200,
            "success" => true,
            "data" =>  $this->createInputFunction()
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
                $cond = $cond . $line[$i];
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
                    $cond = $cond . $line[$i];
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



    private function  createInputFunction()
    {
        $fInputRef = "";
        $fInputCode = "";
        foreach ($this->lstVariables as $v) {
            $variable = "ref " . current($v) . " " . key($v) . ",";

            $fInputRef = $fInputRef . $variable;
            $fInputCode = $fInputCode . "\n\t\t\tConsole.WriteLine(\"Nhap ".key($v).": \");";
            if (value($v) != "String")
                $InputCode = $fInputCode . '"\n\t\t\t'.key($v).' = '.current($v).'.Parse(Console.ReadLine());';
            else
                $fInputCode = $fInputCode . "\n\t\t\tConsole.ReadLine();";
        }
        $fInputRef = substr($fInputRef, 0,  strlen($fInputRef) - 1);
        return "private function $this->fName($fInputRef) { $fInputCode \t\t}\n";
    }

    private function createOutputFunction()
    {
        $fInputRef = "";
        $InputRef = $this->rsType . " " . $this->rsName;
        $fOutputCode = "\n\t\t\tConsole.WriteLine(\"Ket qua la: \", ".$this->rsName.");\n";
        return "public void Xuat_".$this->fName."(".$InputRef.")\n{".$fOutputCode."\n\t\t}\n";
    }


    private function createCheckFunction()
    {
        $fInputRef = "";
        $cond = "";
        if ($this->condition == "")
            $this->cond = "\t\t\treturn true;\n";
        else
            $this->cond = "\t\t\treturn {condition};\n";

        foreach ($this->lstVariables as $v) {
            $variable = current($v) . " " . key($v) . ",";
            $fInputRef =  $fInputRef.$variable;
        }
        $fInputRef = substr($fInputRef, 0, strlen($fInputRef) - 1);
        return "private function KiemTra_".$this->fName."($fInputRef)\n\t\t{\n".$cond."\t\t\n}";
    }

    private function  createResultFunction()
    {
        $input = "";
        $calculate = "";
        $rsString = "\n\t\t\t$this->rsType $this->rsName = {initValue[$this->rsType}";
        foreach ($this->lstVariables as $v) {
            $input =  $input.current($v)." ".key($v).",";
        }
        if (count($this->tmp) == 1) {
            $calculate = "\n\t\t\t" + $this->tmp[0] + ";";
        } else {
            foreach ($this->tmp as $c) {
                $replaceStr = "";
                $arr = explode('&', $c);
                foreach ($arr as $item) {
                    if (strpos($item, $this->rsName) != -1) {
                        $replaceStr = $item;
                    }
                }

                $cond = str_replace($replaceStr . "&&", '', $c);
                $cond = str_replace("&&" . $replaceStr, '', $cond);


                //Console.WriteLine(c.Replace(replaceStr + "&&", "").Trim());
                $replaceStr = trim($replaceStr);
                if ($replaceStr[0] == '(')
                    $replaceStr = substr(trim($replaceStr), 0, 1);
                else {

                    echo $replaceStr;
                }
                if ($replaceStr[strlen($replaceStr) - 1] == ')')
                    $replaceStr =  substr(trim($replaceStr), 0, strlen($replaceStr) - 1);
                $this->cond = str_replace("!=", "not__equal", $this->cond);
                $this->cond = str_replace(">=", "greater__equal", $this->cond);
                $this->cond = str_replace("<=", "less_equal", $this->cond);
                $this->cond = str_replace("=", "==", $this->cond);
                $this->cond = str_replace("not__equal", "!=", $this->cond);
                $this->cond = str_replace("greater__equal", ">=", $this->cond);
                $this->cond = str_replace("less__equal", "<=", $this->cond);
                $calculate = $calculate . "\n\t\t\tif($cond)";
                $calculate = $calculate . "\n\t\t\t\t$replaceStr;";
            }
        }
        $input = substr($input, 0, strlen($input) - 1);
        return "private function $this->rsType $this->fName($input)" .
            "\n\t\t{{" .
            "$rsString;" .
            "$calculate" .
            "\n\t\t\treturn $this->rsName;" .
            "\n\t\t}}";
    }


    private function   createMainFunction()
    {
        //create var

        $mainCode = "";
        $varString = "";
        $inputWithRef = "";
        $input = "";
        foreach ($this->lstVariables as $v) {
            $varString = $varString . "\n\t\t\t".current($v)." ".key($v)." = ".$this->initValue[current($v)].";";
            $input = $input . "".key($v).",";
            $inputWithRef = $inputWithRef . "ref ".key($v).",";
        }
        $varString = $varString . "\n\t\t\t$this->rsType $this->rsName = ".$this->initValue[$this->rsType].";";
        $input = $input . substr($input, 0, strlen($input) - 1);
        $inputWithRef = $inputWithRef . substr($inputWithRef, 0, strlen($inputWithRef) - 1);
        $varString = $varString . "\n\t\t\tProgram p = new Program();";
        $mainCode = $mainCode . "\n\t\t\tp.Nhap_$this->fName($inputWithRef);";
        $mainCode = $mainCode . "\n\t\t\tif(p.KiemTra_$this->fName($input))" .
            "\n\t\t\t{{" .
            "\n\t\t\t\t" .
            "$this->rsName = p.$this->fName($input);" .
            "\n\t\t\t\tp.Xuat_$this->fName($this->rsName);" .
            "\n\t\t\t}}" .
            "\n\t\t\telse\n\t\t\t\tConsole.WriteLine(\"Thong tin nhap khong hop le\");" .
            "\n\t\t\tConsole.ReadLine();";
        return "public static function main()\n\t\t{{".$varString." ".$mainCode."\n\t\t}}";
    }

    private function  printFunctionInfo()
    {
        $rs = "Function: " . $this->fName;
        $rs = $rs . "\nVar : ";
        foreach ($this->lstVariables as $v) {
            $variable = key($v) . " : " . current($v) . " ";
            $rs = $rs . $variable;
        }
        $rs = $rs . "\nResult: " . $this->rsName . " : " . $this->rsType;
        $rs = $rs . "\nCondition: " . $this->condition;
        $rs = $rs . "\nPost: " . $this->post;
        //MessageBox.Show(rs);
    }

    private function   printResult()
    {
        $inputFunction = $this->createInputFunction();
        $outputFunction = $this->createOutputFunction();
        $checkFunction = $this->createCheckFunction();
        $resultFunction = $this->createResultFunction();
        $mainFunction = $this->createMainFunction();
        $result = "using System;\nnamespace FormalSpecification\n{{\n\tpublic class Program\n\t{{\n" .
            "{inputFunction}" .
            "{outputFunction}" .
            "{checkFunction}" .
            "{resultFunction}" .
            "{mainFunction}" .
            "\n\t}}\n}}";
        $this->outputText = $result;
    }
}
