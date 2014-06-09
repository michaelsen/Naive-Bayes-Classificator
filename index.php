<?php
    ini_set('max_execution_time', 6000);

    function naiveBayes()
    {
        $grouping = loadFiles();

        $filename = "result.txt";
        $file = fopen($filename, 'w');
        fwrite($file, "\n");//erases everything

        $mean = array("TruePositive" => 0, "FalsePositive" => 0, "TrueNegative" => 0, "FalseNegative" => 0);
        foreach ($grouping as $key=> $group) 
        {
            file_put_contents($filename, "[TESTING SET = GROUP "."$key"."]================================\r\n", FILE_APPEND);
            echo "<h3>[TESTING SET = GROUP "."$key"."]================================</h3>";
            $data = trainingPhase($grouping, $key);
            $info = testingPhase($group, $data);
            foreach ($info as $key => $value) 
            {
                file_put_contents($filename, "\t"."$key"." = "."$value"."\r\n", FILE_APPEND);
                echo "<p>"."$key"." = "."$value"."</p>";
            }
            file_put_contents($filename, "\r\n\r\n", FILE_APPEND);
            foreach ($mean as $key => $value) 
            {
                $mean[$key] += $info[$key];
            }
        }
        $filecountPositive = count($grouping[0]["positive"])*10;
        $filecountNegative = count($grouping[0]["negative"])*10;

        $mean["Accuracy"] = ($mean["TruePositive"]+$mean["TrueNegative"])/($filecountPositive + $filecountNegative);
        $mean["Sensibility(TruePositiveRate)"] = $mean["TruePositive"] / $filecountPositive;
        $mean["FalsePositiveRate"] = $mean["FalsePositive"] / $filecountNegative;
        $mean["Specificity"] = $mean["TrueNegative"] / $filecountNegative;
        $mean["Efficiency"] = ($mean["Sensibility(TruePositiveRate)"] + $mean["Specificity"])/2;
        $mean["Precision"] = $mean["TruePositive"] / ($mean["TruePositive"] + $mean["FalsePositive"]);
        $mean["NegativePrecision"] = $mean["TrueNegative"] / ($mean["TrueNegative"] + $mean["FalseNegative"]);
        $mean["F-Measure"] = 2 * ($mean["Sensibility(TruePositiveRate)"] * $mean["Precision"]) / ($mean["Sensibility(TruePositiveRate)"] + $mean["Precision"]);

        $mean["TruePositive"] = $mean["TruePositive"]/10;
        $mean["FalsePositive"] = $mean["FalsePositive"]/10;
        $mean["FalseNegative"] = $mean["FalseNegative"]/10;
        $mean["TrueNegative"] = $mean["TrueNegative"]/10;

        $deviation = standardDeviation($info, $mean);
        
        $mean["StandardDeviation-TruePositive"] = $deviation["TruePositive"];
        $mean["StandardDeviation-FalsePositive"] = $deviation["FalsePositive"];
        $mean["StandardDeviation-FalseNegative"] = $deviation["FalseNegative"];
        $mean["StandardDeviation-TrueNegative"] = $deviation["TrueNegative"];

        file_put_contents($filename, "\r\n[MEAN]================================\r\n", FILE_APPEND);
        echo "<h1>[MEAN]================================</h1>";
        foreach ($mean as $key => $value) 
        {
            file_put_contents($filename, "\t"."$key"." = "."$value"."\r\n", FILE_APPEND);
            echo "<p>"."$key"." = "."$value"."</p>";
        }

        fclose($file);
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
        
        $wordcountPositive = 0;
        $wordcountNegative = 0;
        $vocabulary = 0;

        foreach ($trainingSet as $key => $group) 
        {
            if ($key != $ignore) 
            {
                foreach ($group["positive"] as $key => $file)
                {
                    $wordcountPositive += count($file);
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
                    $wordcountNegative += count($file);
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
            $data["positive"][$word] = log(($count + 1)/($vocabulary + $wordcountPositive));
        }
        foreach ($data["negative"] as $word => $count)
        {
            $data["negative"][$word] = log(($count + 1)/($vocabulary + $wordcountNegative));
        }
        $data["positive"]["."] = log(1/($vocabulary + $wordcountPositive));//palavra inexistente
        $data["negative"]["."] = log(1/($vocabulary + $wordcountNegative));//palavra inexistente
        
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
        $info["Sensibility(TruePositiveRate)"] = $info["TruePositive"] / $positiveFiles;
        $info["FalsePositiveRate"] = $info["FalsePositive"] / $negativeFiles;
        $info["Specificity"] = $info["TrueNegative"] / $negativeFiles;
        $info["Efficiency"] = ($info["Sensibility(TruePositiveRate)"] + $info["Specificity"])/2;
        $info["Precision"] = $info["TruePositive"] / ($info["TruePositive"] + $info["FalsePositive"]);
        $info["NegativePrecision"] = $info["TrueNegative"] / ($info["TrueNegative"] + $info["FalseNegative"]);
        $info["F-Measure"] = 2 * ($info["Sensibility(TruePositiveRate)"] * $info["Precision"]) / ($info["Sensibility(TruePositiveRate)"] + $info["Precision"]);

        return $info;
    }

    function evaluate($file, $data)
    {//positivo  = true, negativo = false
        $positive = 0;
        $negative = 0;
        
        foreach ($file as $key => $word)
        {
            if (isset($data["positive"][$word])) 
            {
                $positive += $data["positive"][$word];
            }
            else
            {
                $positive += $data["positive"]["."];
            }

            if (isset($data["negative"][$word])) 
            {
                $negative += $data["negative"][$word];
            }
            else
            {
                $negative += $data["negative"]["."];
            }
        }

        return ($positive >= $negative);
    }

    function standardDeviation($values, $mean)
    {
        $sum = array("TruePositive" => 0, "FalsePositive" => 0, "TrueNegative" => 0, "FalseNegative" => 0);
        foreach ($values as $key => $iteration) 
        {
            $sum["TruePositive"] += pow(($iteration["TruePositive"] - $mean["TruePositive"]), 2);
            $sum["FalsePositive"] += pow(($iteration["FalsePositive"] - $mean["FalsePositive"]), 2);
            $sum["FalseNegative"] += pow(($iteration["FalseNegative"] - $mean["FalseNegative"]), 2);
            $sum["TrueNegative"] += pow(($iteration["TrueNegative"] - $mean["TrueNegative"]), 2);            
        }
        foreach ($sum as $key => $value) 
        {
            $sum[$key] = $value/9; //n-1 pra 10-fold é 9
            $sum[$key] = sqrt($sum[$key]);
        }

        return $sum;
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
