<?php

require('../XMLToStruct.php');

$xml = "
    <echo>
        <another>
        <0>last

        oh well
        ':\"
        </0>
            <1>3</1>
            <2>7</2>
            <another>
                <0>last</0>
                <1>3</1>
                <2>7</2>
            </another> 
        </another>
        <something>2</something>
        <some>2</some>
    </echo>";

#$xml = "<error><module></module><message>DIE at ../../src/perl/modules//PLAZA/echo.pm line 14.
#     at ../../src/perl/modules//PLAZA/echo.pm line 14
#        PLAZA::echo::echo({'url' => 'PLAZA/echo/echo','locus_id' => 'AT01G01010','release' => '666','echothis' => 'hi','genome' => 'arabisopsis'}) called at (eval 12) line 1
#            eval 'echo({genome => \'arabisopsis\',release => \'666\',locus_id => \'AT01G01010\',echothis => \'hi\',url => \'PLAZA/echo/echo\'})
#            ;' called at ../../src/perl/modules//PLAZA/WebService.pm line 139
#                PLAZA::WebService::execute_function({'params' => 'genome => \'arabisopsis\',release => \'666\',locus_id => \'AT01G01010\',echothis => \'hi\',url => \'PLAZA/echo/echo\'','function' => 'echo','url' => 'PLAZA/echo/echo','locus_id' => 'AT01G01010','release' => '666','echothis' => 'hi','genome' => 'arabisopsis','module' => 'PLAZA::echo'}, '') called at ../../src/perl/modules//PLAZA/WebService.pm line 97
#                    PLAZA::WebService::request_handler(bless( {'.parameters' => ['genome','release','locus_id','echothis','url'],'locus_id' => ['AT01G01010'],'release' => ['666'],'echothis' => ['hi'],'genome' => ['arabisopsis'],'.charset' => 'ISO-8859-1','url' => ['PLAZA/echo/echo'],'.fieldnames' => {},'.header_printed' => 1,'escape' => 1}, 'CGI' ), '../../src/perl/modules/', '/PLAZA/echo/echo', '') called at /www/group/biocomp/extra/bioinformatics_prod/testix/kebil/trunk/www/ws/WebService.pl line 36
#                    </message></error>";


$parser = new XMLToStruct($xml);
print_r($parser->getResult());

?>
