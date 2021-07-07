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

    protected $c ;

    public function handle(Request $request)
    {



      
        //         $this->inputText = "SoLonHon(a:R,b:R)c:R
        // pre 
        // post ((c=a)&&(a>=b))||((c=b)&&(b>a))";

        //         $lines = explode("\n", $this->inputText);

        //         $this->getVariableInfo($lines[0]);
        //         //$this->lstVariables

        //         $this->getResult($lines[0]);
        //         // $this->rsName
        //         // $this->rsType

        //         $this->getFunctionName($lines[0]);
        //         // $this->fName

        //         $this->getCondition($lines[1]);
        //         // $this->condition

        //         $this->getPost($lines[2]);
        //         // $this->post

        //         $this->getMainFunction($this->post);
        //         // $this->tmp


        //createInputFunction()

        $this->split_text($request);

        $this->createInputFunction();
        $this->createCheckFunction();
        $this->createResultFunction();
        $this->createOutputFunction();



        $textHandle =
            $this->createInputFunction() .
            $this->createCheckFunction() .
            $this->createResultFunction();
            $this->createOutputFunction();


            //  echo $textHandle;

        eval($textHandle);



        // echo $this->createMainFunction();

        return response()->json([
            "code" => 200,
            "success" => true,

            "data" =>  $this->c,
            "text_handle" => $textHandle
        ], 200);
    }


    public function  split_text(Request $request, $get_availible = false)
    {

        $lines = explode("\n", $request->text);

        $currentLine = 0;
        $fInfoString = "";
        $cInfoString = "";
        $pInfoString = "";
        while (strlen(trim($lines[$currentLine])) < 3 || ($currentLine < count($lines) - 1 && substr(trim($lines[$currentLine]), 0, 3) != "pre")) {
            $fInfoString = $fInfoString . trim($lines[$currentLine]);
            $currentLine++;
        }
        $this->getVariableInfo($fInfoString);
        $this->getFunctionName($fInfoString);

        if ($get_availible == false) {
            $this->getResult($fInfoString);

            while (strlen(trim($lines[$currentLine])) < 4 || ($currentLine < count($lines) - 1 && substr(trim($lines[$currentLine]), 0, 4) != "post")) {
                $fInfoString = $fInfoString . trim($lines[$currentLine]);
                $currentLine++;
            }
            $this->getCondition($cInfoString);
            while ($currentLine < count($lines)) {
                $pInfoString = $pInfoString . trim($lines[$currentLine]);
                $currentLine++;
            }
            $this->getPost($pInfoString);
            $this->getMainFunction($this->post);
        }
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

        $this->rsType = $this->typesName[trim(explode(':', $resultString)[1])];
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
        $this->post = trim(substr($line, 4));
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
            $variable = "\$" . key($v) . ",";

            $fInputRef = $fInputRef . $variable;
            $fInputCode = $fInputCode . "\n\t\t\t $" . key($v) . "= \$request->" . key($v) . ";";
            if (value($v) != "String")
                $InputCode = $fInputCode . '"\n\t\t\t $' . key($v) . ' = ' . current($v) . ';';
            else
                $fInputCode = $fInputCode . "\n\t\t\t ";
        }
        $fInputRef = substr($fInputRef, 0,  strlen($fInputRef) - 1);
        //return "public function Nhap_$this->fName($fInputRef) {

        return "\n ///// Danh sach bien input  $fInputCode \t\t \n \n";
    }

    private function createOutputFunction()
    {
      
        $fInputRef = "";
        $InputRef = "\$$this->rsName";
        $fOutputCode = "\n\t\t\t echo(\"Ket qua la: $" . $this->rsName . " \");\n";
        //return "\n///// Ham Xuat  \n " . $this->fName . "(" . $InputRef . ")\n{" . $fOutputCode . "\n\t\t}\n";
        return "\n///// Ham Xuat  \n " . "  $fOutputCode " . "\n\t\t \n";
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
            $fInputRef =  $fInputRef . $variable;
        }
        $fInputRef = substr($fInputRef, 0, strlen($fInputRef) - 1);
        return "\n///// Kiem tra dieu kien bien  " . $this->cond . "\t\t   \n";
    }

    private function  createResultFunction()
    {

        $input = "";
        $calculate = "";
        $rsString = "\n\t\t\t$$this->rsName = " . $this->initValue[$this->rsType] . " ";
        foreach ($this->lstVariables as $v) {
            $input =  $input . current($v) . " " . key($v) . ",";
        }
        if (count($this->tmp) == 1) {
            $calculate = "\n\t\t\t" . $this->tmp[0] . ";";
        } else {
            foreach ($this->tmp as $c) {
                $replaceStr = "";
                $arr = explode('&', $c);
                foreach ($arr as $item) {
                    if (strpos($item, $this->rsName) != false) {
                        $replaceStr = $item;
                    }
                }

                $cond = str_replace($replaceStr . "&&", '', $c);
                $cond = str_replace("&&" . $replaceStr, '', $cond);


                //Console.WriteLine(c.Replace(replaceStr + "&&", "").Trim());
                $replaceStr = trim($replaceStr);
                if ($replaceStr[0] == '(')
                    $replaceStr = substr(trim($replaceStr), 1);
                else {

                    echo $replaceStr;
                }
                if ($replaceStr[strlen($replaceStr) - 1] == ')')
                    $replaceStr =
                        substr(trim($replaceStr), 0, strlen($replaceStr) - 1);
                $cond = str_replace("!=", "not__equal", $cond);
                $cond = str_replace(">=", "greater__equal", $cond);
                $cond = str_replace("<=", "less_equal", $cond);
                $cond = str_replace("=", "==", $cond);
                $cond = str_replace("not__equal", "!=", $cond);
                $cond = str_replace("greater__equal", ">=", $cond);
                $cond = str_replace("less__equal", "<=", $cond);
                $calculate = $calculate . "\n\t\t\tif($cond)";
                $calculate = $calculate . "\n\t\t\t\t$replaceStr;";


                $pattern = '/[a-z]+/';
                $replacement = '$${0}';
                $calculate = preg_replace($pattern, $replacement, $calculate);

                $calculate = str_replace("$$", "$", $calculate);
                $calculate = str_replace("\$if", "if", $calculate);
                $calculate = str_replace("\$FALSE", "FALSE", $calculate);
                $calculate = str_replace("\$TRUE", "TRUE", $calculate);
            }
        }
        $input = substr($input, 0, strlen($input) - 1);

       

        return " \n///// Ham xu ly \n " .
            "$rsString;" .
            "$calculate" .
            // "\n\t\t\treturn $this->rsName;" .
            // \n\t\t}
            " \n 
            \$this->c = $".$this->rsName.";
            ";
    }




    public function test_code(Request $request)
    {
        ///// Danh sach bien input  
        $a = $request->a;
        $b = $request->b;


        ///// Kiem tra dieu kien bien  
        ///// Ham xu ly 

        $c = 0;
        if ($a >= $b)
            $c = $a;
        if ($b > $a)
            $c = $b;

        ///// Ham Xuat  

        echo ("Ket qua la: $c ");
    }

    public function variable(Request $request)
    {

        if ($request->text != null) {
            $this->split_text($request, true);

            return response()->json([
                "code" => 200,
                "success" => true,
                "func_name" => $this->fName,
                "data" =>  $this->lstVariables,
            ], 200);
        } else {

            return response()->json([
                "code" => 200,
                "success" => true,
                "data" =>  null
            ], 200);
        }
    }
}
