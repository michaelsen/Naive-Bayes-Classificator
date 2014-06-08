<?php
    ini_set('max_execution_time', 6000);

    function naiveBayes()
    {
        $grouping = loadFiles();

        $filename = "result.txt";
        $file = fopen($filename, 'w');

        foreach ($grouping as $key=> $group) 
        {
            fwrite($file, "[TESTING SET = GROUP "."$key+1"."]================================\n");
            $data = trainingPhase($grouping, $key);
            $info = testingPhase($group, $data);
            file_put_contents($filename, $info);
            fwrite($file, "\n\n");
        }

    }

    function loadFiles()
    {
        $positiveFiles = loadFilesByClassification("positivo");
        $negativeFiles = loadFilesByClassification("negativo"); 
        $grouping = divide($positiveFiles, $negativeFiles);
        return $grouping;
    }

    function loadFilesByClassification($class)
    {
        $filename = "filter.txt";
        $file = fopen($filename,'r');
        $data = file_get_contents($filename);
        $data = utf8_encode($data);
        $filter = explode(" ", $data);
        fclose($file);

        $filename = "symbols.txt";
        $file = fopen($filename,'r');
        $data = file_get_contents($filename);
        $data = utf8_encode($data);
        $symbols = explode(" ", $data);
        fclose($file);
        
        $dataArray = array();
        $fileData = array();
        for ($iterator=19; $iterator < 219; $iterator++)
        { //meus arquivos vão do 19 ao 218
            $filename = "$class".'/'."$iterator".".txt";
            $file = fopen($filename,'r');
            $fileData = strtolower(file_get_contents($filename));
            $fileData = str_replace($symbols, " ", $fileData);
            // $fileData = preg_split('/[\s,.():;!?-]+/', $fileData, -1, PREG_SPLIT_NO_EMPTY);
            $fileData = explode(" ", $fileData);
            fclose($file);

            $fileData = filterData($fileData, $filter);
            // $feedback = implode(" ", $fileData);
            // echo "$feedback"."<br><br>";
            array_push($dataArray, $fileData);
        }
        return $dataArray;        
    }

    function filterData($data, $filter)
    {
        foreach ($data as $key => $word) 
        {
            foreach ($filter as $garb)
            {
                if(strcmp($word,$garb) == 0 || strlen($word) <= 1)
                {
                    unset($data[$key]);
                }
            }
        }
        return $data;
    }

    function divide($positive, $negative)
    {
        $grouping = array();
        
        $size = (int) count($positive) / 10;
        
        for ($index=0; $index < count($positive) ; $index++)
        { 
            $group = (int) ($index / $size);
            
            $grouping[$group]["positive"][] =  $positive[$index];
            $grouping[$group]["negative"][] =  $negative[$index];
        }
        return $grouping;
    }

    function trainingPhase($trainingSet, $ignore)
    {
        $data = array();
        
        $wordcount = 0;
        $vocabulary = 0;

        foreach ($trainingSet as $key => $group) 
        {
            if ($key != $ignore) 
            {
                foreach ($group["positive"] as $key => $file)
                {
                    $wordcount += count($file);
                    foreach ($file as $word) 
                    {
                        if (isset($data["positive"][$word]))
                        {
                            $data["positive"][$word]++;
                        }
                        else
                        {
                            $data["positive"][$word] = 1;
                            $vocabulary++;
                        }
                    }
                }
                foreach ($group["negative"] as $key => $file)
                {
                    $wordcount += count($file);
                    foreach ($file as $word) 
                    {
                        if (isset($data["negative"][$word]))
                        {
                            $data["negative"][$word]++;
                        }
                        else
                        {
                            $data["negative"][$word] = 1;
                            if (!isset($data["positive"][$word]))//palavra não apareceu nos positivos, logo é nova
                            {
                                $vocabulary++;
                            }
                        }
                    }
                }
            }
        }

        foreach ($data["positive"] as $word => $count)
        {
            $data["positive"][$word] = ($count + 1);///($vocabulary + $wordcount);
        }
        foreach ($data["negative"] as $word => $count)
        {
            $data["negative"][$word] = ($count + 1);///($vocabulary + $wordcount);
        }
        $data["positive"]["."] = 1;///($vocabulary + $wordcount);//palavra inexistente
        $data["negative"]["."] = 1;///($vocabulary + $wordcount);//palavra inexistente
        $data["count"] = $vocabulary + $wordcount;

        return $data;
    }

    function testingPhase($testingSet, $data)
    {
        $positiveFiles = count($testingSet["positive"]);
        $negativeFiles = count($testingSet["negative"]);

        $positiveEvaluation = 0;
        $negativeEvaluation = 0;

        foreach ($testingSet["positive"] as $key => $file)
        {
            $isPositive = evaluate($file, $data);
            if ($isPositive) 
            {
                $positiveEvaluation++;
            }
        }
        foreach ($testingSet["negative"] as $key => $file)
        {
            $isPositive = evaluate($file, $data);
            if (!$isPositive) 
            {
                $negativeEvaluation++;
            }
        }

        $info["TruePositive"] = $positiveEvaluation;
        $info["FalsePositive"] = $negativeFiles - $negativeEvaluation;
        $info["FalseNegative"] = $positiveFiles - $positiveEvaluation;
        $info["TrueNegative"] = $negativeEvaluation;

        $info["Accuracy"] = ($info["TruePositive"]+$info["TrueNegative"])/($positiveFiles + $negativeFiles);
        $info["Sensitivity"] = $info["TruePositive"] / $positiveFiles;
        $info["Specificity"] = $info["TrueNegative"] / $negativeFiles;
        $info["Efficiency"] = ($info["Sensitivity"] + $info["Specificity"])/2;
        $info["Precision"] = $info["TruePositive"] / ($info["TruePositive"] + $info["FalsePositive"]);
        $info["NegativePrecision"] = $info["TrueNegative"] / ($info["TrueNegative"] + $info["FalseNegative"]);
        $info["F-Measure"] = 2 * ($info["Sensitivity"] * $info["Precision"]) / ($info["Sensitivity"] + $info["Precision"]);

        return $info;
    }

    function evaluate($file, $data)
    {//positivo  = true, negativo = false
        $positive = 1;
        $negative = 1;
        foreach ($file as $key => $word)
        {
            if (isset($data["positive"][$word])) 
            {
                $positive = $positive * $data["positive"][$word];
            }
            else
            {
                $positive = $positive * $data["positive"]["."];
            }

            if (isset($data["negative"][$word])) 
            {
                $negative = $negative * $data["negative"][$word];
            }
            else
            {
                $negative = $negative * $data["negative"]["."];
            }
        }

        return ($positive >= $negative);
    }

?>
<html>
<head>
    <META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=UTF-8">
    <title>Classificador Naïve-Bayes</title>
</head>
<body>
    <?php 
        naiveBayes();
    ?>
</body>
</html>
