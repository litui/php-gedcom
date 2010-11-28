<?php

namespace Gedcom;

require_once __DIR__ . '/Parser/Base.php';
require_once __DIR__ . '/Gedcom.php';
require_once __DIR__ . '/Parser/Object.php';
require_once __DIR__ . '/Parser/Individual.php';

/**
 *
 *
 */
class Parser extends Parser\Base
{
    
    /**
     *
     *
     */
    public function parseFile($fileName)
    {
        $contents = file_get_contents($fileName);
        
        $this->_file = explode("\n", mb_convert_encoding($contents, 'UTF-8'));
        
        $this->_gedcom = new Gedcom();
        
        while($this->_currentLine < count($this->_file))
        {
            $record = $this->getCurrentLineRecord();
            
            // We only process 0 level records here. Sub levels are processed
            // in methods for those data types (individuals, sources, etc)
            
            if((int)$record[0] == 0)
            {
                // Although not always an identifier (HEAD,TRLR):
                $identifier = trim(trim($record[1], '@'));
               
                if(trim($record[1]) == 'HEAD')
                {
                    // TODO
                }
                else if(isset($record[2]) && trim($record[2]) == 'SUBN')
                {
                    // TODO SUBMISSION
                }
                else if(isset($record[2]) && trim($record[2]) == 'SUBM')
                {
                    // TODO SUBMITER
                }
                else if(trim($record[1]) == 'TRLR')
                {
                    // EOF
                    break;
                }
                else if(isset($record[2]) && $record[2] == 'SOUR')
                {
                    $source = $this->_gedcom->createSource($identifier);
                    $this->parseSource($source);
                }
                else if(isset($record[2]) && $record[2] == 'INDI')
                {
                    $person = $this->_gedcom->createPerson($identifier);
                    $this->parsePerson($person);
                }
                else if(isset($record[2]) && $record[2] == 'FAM')
                {
                    $family = $this->_gedcom->createFamily($identifier);
                    $this->parseFamily($family);
                }
                else if(isset($record[2]) && $record[2] == 'NOTE')
                {
                    $note = $this->_gedcom->createNote($identifier);
                    $this->parseNote($note);
                }
                else
                {
                    $this->logUnhandledRecord(__LINE__);
                }
            }
            
            $this->_currentLine++;
        }
        
        return $this->_gedcom;
    }
    
    
    /**
     *
     *
     */
    protected function parseSource(&$source)
    {
        $this->_currentLine++;
        
        while($this->_currentLine < count($this->_file))
        {
            $record = $this->getCurrentLineRecord('S');
            
            if($record[0] == '0')
            {
                $this->_currentLine--;
                
                break;
            }
            else if($record[0] == '1' && trim($record[1]) == 'TITL')
            {
                $source->title = trim($record[2]);
            }
            else if($record[0] == '1' && trim($record[1]) == 'RIN')
            {
                $source->rin = trim($record[2]);
            }
            else if($record[0] == '1' && trim($record[1]) == 'AUTH')
            {
                $source->author = trim($record[2]);
            }
            else if($record[0] == '1' && trim($record[1]) == 'PUBL')
            {
                $source->published = trim($record[2]);
            }
            else if($record[0] == '1' && trim($record[1]) == 'NOTE')
            {
                if(isset($record[2]) && preg_match('/\@N([0-9]*)\@/i', $record[2]) > 0)
                {
                    $source->addNote($this->normalizeIdentifier($record[2], 'N'));
                }
                else
                {
                    $inlineNote = $record[2];
                    
                    $this->_currentLine++;
                    
                    while($this->_currentLine < count($this->_file))
                    {
                        $record = $this->getCurrentLineRecord();
                        
                        if((int)$record[0] <= 1)
                        {
                            $this->_currentLine--;
                            break;
                        }
                        
                        switch($record[1])
                        {
                            case 'CONT':
                                if(isset($record[2]))
                                    $inlineNote .= "\n" . trim($record[2]);
                            break;
                            
                            case 'CONC':
                                if(isset($record[2]))
                                    $inlineNote .= ' ' . trim($record[2]);
                            break;
                        }
                        
                        $this->_currentLine++;
                    }
                    
                    $source->addInlineNote($inlineNote);
                }
            }
            else if((int)$record[0] == 1 && trim($record[1]) == 'CHAN')
            {
                $this->_currentLine++;
                
                $source->change = new \Gedcom\Record\Change();
                
                while($this->_currentLine < count($this->_file))
                {
                    $record = $this->getCurrentLineRecord();
                    
                    if((int)$record[0] <= 1)
                    {
                        $this->_currentLine--;
                        break;
                    }
                    else if((int)$record[0] == 2 && trim($record[1] == 'DATE'))
                    {
                        if(isset($record[2]))
                            $source->date = trim($record[2]);
                    }
                    else if((int)$record[0] == 3 && trim($record[1] == 'TIME'))
                    {
                        if(isset($record[2]))
                            $source->time = trim($record[2]);
                    }
                    else
                    {
                        $this->logUnhandledRecord(__LINE__);
                    }
                    
                    $this->_currentLine++;
                }
            }
            /*else if((int)$record[0] > 1)
            {
                // do nothing, this should be handled in cases above by
                // passing off code execution to other classes
            }*/
            else
            {
                $this->logUnhandledRecord(__LINE__);
            }
            
            $this->_currentLine++;
        }
    }
    
    
    /**
     *
     *
     */
    protected function parsePerson(&$person)
    {
        $this->_currentLine++;
        
        while($this->_currentLine < count($this->_file))
        {
            $record = $this->getCurrentLineRecord('P');
            
            if($record[0] == '0')
            {
                $this->_currentLine--;
                break;
            }
            else if((int)$record[0] == 1 && trim($record[1]) == 'CHAN')
            {
                $this->_currentLine++;
                
                $person->change = new \Gedcom\Record\Change();
                
                while($this->_currentLine < count($this->_file))
                {
                    $subRecord = $this->getCurrentLineRecord();
                    
                    if((int)$subRecord[0] <= 1)
                    {
                        $this->_currentLine--;
                        break;
                    }
                    else if((int)$subRecord[0] == 2 && trim($subRecord[1] == 'DATE'))
                    {
                        if(isset($subRecord[2]))
                            $person->change->date = trim($subRecord[2]);
                    }
                    else if((int)$subRecord[0] == 3 && trim($subRecord[1] == 'TIME'))
                    {
                        if(isset($subRecord[2]))
                            $person->change->time = trim($subRecord[2]);
                    }
                    else if(trim($subRecord[1]) == 'NOTE')
                    {
                        $note = $this->parseDataElementNote($record[0]);
                        
                        if(is_a($note, "\\Gedcom\\Record\\Note"))
                        {
                            $person->change->notes[] = $note;
                        }
                        else if(is_a($note, "\\Gedcom\\Record\\Note\\Reference"))
                        {
                            $person->change->note_references[] = $note;
                        }
                    }
                    else
                    {
                        $this->logUnhandledRecord(__LINE__);
                    }
                    
                    $this->_currentLine++;
                }
            }
            else if($record[0] == '1')
            {
                $recordType = trim($record[1]);
                
                $handler = 'parse' . $recordType . 'Record';
                
                if(is_callable(array($this, $handler)))
                {
                    if(isset($record[2]))
                        $this->$handler($person, trim($record[2]), 1);
                    else
                        $this->$handler($person, 1);
                }
                else
                {
                    $this->logUnhandledRecord(__LINE__);
                }
            }
            /*else if((int)$record[0] > 1)
            {
                // do nothing, this should be handled in cases above by
                // passing off code execution to other classes
            }*/
            else
            {
                $this->logUnhandledRecord(__LINE__);
            }
            
            $this->_currentLine++;
        }
    }
    
    /**
     *
     *
     */
    public function parseDataElementNote()
    {
        $record = $this->getCurrentLineRecord();
    
        if(!isset($record[1]) || trim($record[1]) != 'NOTE')
            throw new Exception('Expected to find NOTE at current line');

        $note = null;
                        
        if(isset($record[2]) && preg_match('/\@N([0-9]*)\@/i', $record[2]) > 0)
        {
            $note = new \Gedcom\Record\Note\Reference();
            $note->noteId = trim(trim($record[2], '@'));
        }
        else if(isset($record[2]))
        {
            $note = new \Gedcom\Record\Note();
            $note->note = $record[2];

            $this->_currentLine++;
            
            while($this->_currentLine < count($this->_file))
            {
                $sub = $this->getCurrentLineRecord();
                
                if((int)$sub[0] <= (int)$record[0])
                {
                    $this->_currentLine--;
                    break;
                }
                
                switch($sub[1])
                {
                    case 'CONT':
                        if(isset($sub[2]))
                            $note->note .= "\n" . trim($sub[2]);
                    break;
                    
                    case 'CONC':
                        if(isset($sub[2]))
                            $note->note .= ' ' . trim($sub[2]);
                    break;
                    
                    default:
                        $this->logUnhandledRecord(__LINE__);
                    break;
                }
                
                $this->_currentLine++;
            }
        }
        else
        {
            $this->logUnhandledRecord(__LINE__);
        }
        
        return $note;
    }
    
    
    /**
     *
     *
     */
    protected function parseFamily(&$family)
    {
        $this->_currentLine++;
        
        while($this->_currentLine < count($this->_file))
        {
            $record = $this->getCurrentLineRecord();
            
            if($record[0] == '0')
            {
                $this->_currentLine--;
                break;
            }
            else if($record[0] == '1')
            {
                $recordType = trim($record[1]);
                
                $familyId = trim(trim($record[1]), '@F');
                
                switch($recordType)
                {
                    case 'HUSB':
                        $family->husbandId = trim(trim($record[2]), '@I');
                    break;    
                    
                    case 'WIFE':
                        $family->wifeId = trim(trim($record[2]), '@I');
                    break;
                    
                    case 'CHIL':
                        $family->children[] = trim(trim($record[2]), '@I');
                    break;
                    
                    case 'MARR':
                        $this->parseEventRecord($family, 'marriage');
                    break;
                    
                    case 'DIV':
                        $this->parseEventRecord($family, 'divorce');
                    break;
                    
                    case 'RIN':
                        $family->rin = trim($record[2]);
                    break;
                    
                    case 'NOTE':
                        $family->notes[] = trim(trim($record[2]), '@N');
                    break;
                
                    case 'CHAN':
                        $this->_currentLine++;
                        
                        $family->change = new \Gedcom\Record\Change();
                        
                        while($this->_currentLine < count($this->_file))
                        {
                            $record = $this->getCurrentLineRecord();
                            
                            if((int)$record[0] <= 1)
                            {
                                $this->_currentLine--;
                                break;
                            }
                            else if((int)$record[0] == 2 && trim($record[1] == 'DATE'))
                            {
                                if(isset($record[2]))
                                    $family->change->date = trim($record[2]);
                            }
                            else if((int)$record[0] == 3 && trim($record[1] == 'TIME'))
                            {
                                if(isset($record[2]))
                                    $family->change->time = trim($record[2]);
                            }
                            else
                            {
                                $this->logUnhandledRecord(__LINE__);
                            }
                            
                            $this->_currentLine++;
                        }
                    break;
                    
                    default:
                        $this->logUnhandledRecord(__LINE__);
                }
            }
            /*else if((int)$record[0] > 1)
            {
                // do nothing, this should be handled in cases above by
                // passing off code execution to other classes
            }*/
            else
            {
                $this->logUnhandledRecord(__LINE__);
            }
            
            $this->_currentLine++;
        }
    }
    
    
    /**
     *
     */
    protected function parseNote(&$note)
    {
        $record = $this->getCurrentLineRecord();

        $startLevel = $record[0];

        if($startLevel > 0)
        {
            //$if(isset($record[2]) && preg_match
        }

        $this->_currentLine++;
        
        while($this->_currentLine < count($this->_file))
        {
            $record = $this->getCurrentLineRecord();
            
            if((int)$record[0] <= 0)
            {
                $this->_currentLine--;
                break;
            }
            else if((int)$record[0] > 0)
            {
                $recordType = trim($record[1]);
                
                switch($recordType)
                {
                    case 'RIN':
                        $note->rin = trim($record[2]);
                    break;
                    
                    case 'CONT':
                        if(isset($record[2]))
                            $note->note .= "\n" . $record[2];
                    break;
                    
                    case 'CONC':
                        if(isset($record[2]))
                            $note->note .= $record[2];
                    break;
                   
                    case 'REFN':
                        $reference = new \Gedcom\Record\ReferenceNumber();
                        
                        if(isset($record[2]))
                            $reference->number = trim($record[2]);

                        $this->_currentLine++;

                        while($this->_currentLine < count($this->_file))
                        {
                            $subRecord = $this->getCurrentLineRecord();

                            if((int)$subRecord[0] <= (int)$record[0])
                            {
                                $this->_currentLine--;
                                break;
                            }
                            else if(isset($subRecord[1]) && trim($subRecord[1]) == 'TYPE')
                            {
                                if(isset($subRecord[2]))
                                    $reference->type = trim($subRecord[2]);
                            }
                            else
                            {
                                $this->logUnhandledRecord(__LINE__);
                            }
                            
                            $this->_currentLine++;
                        }
                    break;

                    case 'CHAN':
                        $this->_currentLine++;
                        
                        while($this->_currentLine < count($this->_file))
                        {
                            $record = $this->getCurrentLineRecord();
                            
                            $note->change = new \Gedcom\Record\Change();
                            
                            if((int)$record[0] <= 1)
                            {
                                $this->_currentLine--;
                                break;
                            }
                            else if((int)$record[0] == 2 && trim($record[1] == 'DATE'))
                            {
                                if(isset($record[2]))
                                    $note->change->date = trim($record[2]);
                            }
                            else if((int)$record[0] == 3 && trim($record[1] == 'TIME'))
                            {
                                if(isset($record[2]))
                                    $note->change->time = trim($record[2]);
                            }
                            else
                            {
                                $this->logUnhandledRecord(__LINE__);
                            }
                            
                            $this->_currentLine++;
                        }
                    break;

                    case 'SOUR':
                        $source = new \Gedcom\Record\Source();

                        $this->parseSource($source);

                        $note->sources[] = $source;
                    break;
                    
                    default:
                        $this->logUnhandledRecord(__LINE__);
                }
            }
            /*else if((int)$record[0] > 0)
            {
                // do nothing, this should be handled in cases above by
                // passing off code execution to other classes
            }*/
            else
            {
                $this->logUnhandledRecord(__LINE__);
            }
            
            $this->_currentLine++;
        }
    }
    
    
    /**
     *
     */
    protected function parseGenericInformation(&$person, $type, $data)
    {
        $this->_currentLine++;
        
        $attribute = $person->addAttribute($type, $data);
        
        while($this->_currentLine < count($this->_file))
        {
            $record = $this->getCurrentLineRecord();
            
            if((int)$record[0] <= 1)
            {
                $this->_currentLine--;
                
                return;
            }
            else if((int)$record[0] == 2)
            {
                $recordType = trim($record[1]);
                
                switch($recordType)
                {
                    case 'SOUR':
                        $reference = $this->_gedcom->createReference($this->normalizeIdentifier($record[2], 'S'), $type);
                        
                        $this->parseReference($reference, $record[0]);
                        
                        $attribute->addReference($reference);
                    break;
                    
                    case 'NOTE':
                        $note = $this->parseDataElementNote();
                        
                        if(is_a($note, "\\Gedcom\\Record\\Note"))
                        {
                            $attribute->notes[] = $note;
                        }
                        else if(is_a($note, "\\Gedcom\\Record\\Note\\Reference"))
                        {
                            $attribute->note_references[] = $note;
                        }
                    break;
                    
                    default:
                        $this->logUnhandledRecord(__LINE__);
                }
            }
            /*else if((int)$record[0] > (int)$atLevel)
            {
                // do nothing, this should be handled in cases above by
                // passing off code execution to other classes
            }*/
            else
            {
                $this->logUnhandledRecord(__LINE__);
            }
            
            $this->_currentLine++;
        }
    }
    
    
    /**
     *
     */
    protected function parseNameRecord(&$person, $value)
    {
        $this->parseGenericInformation($person, 'name', $value);
    }
    
    
    /**
     *
     */
    protected function parseRinRecord(&$person, $value)
    {
        $this->parseGenericInformation($person, 'rin', $value);
    }
    
    
    /**
     *
     */
    protected function parseSexRecord(&$person, $value)
    {
        $this->parseGenericInformation($person, 'sex', $value);
    }
    
    
    /**
     *
     */
    protected function parseFamcRecord(&$person)
    {
        $record = $this->getCurrentLineRecord();
        
        $refId = trim(trim($record[2]), '@F');
        
        $person->famc[$refId] = $refId;
        
        $this->_currentLine++;
        
        while($this->_currentLine < count($this->_file))
        {
            $record = $this->getCurrentLineRecord();
            
            if((int)$record[0] <= 1)
            {
                $this->_currentLine--;
                
                break;
            }
            else
            {
                $this->logUnhandledRecord(__LINE__);
            }
            
            $this->_currentLine++;
        }
    }
    
    
    /**
     *
     */
    protected function parseFamsRecord(&$person)
    {
        $record = $this->getCurrentLineRecord();
        
        $refId = trim(trim($record[2]), '@F');
        
        $person->fams[$refId] = $refId;
        
        $this->_currentLine++;
        
        while($this->_currentLine < count($this->_file))
        {
            $record = $this->getCurrentLineRecord();
            
            if((int)$record[0] <= 1)
            {
                $this->_currentLine--;
                
                break;
            }
            else
            {
                $this->logUnhandledRecord(__LINE__);
            }
            
            $this->_currentLine++;
        }
    }
    
    
    /**
     *
     *
     */
    protected function parseObjeRecord(&$person)
    {
        $record = $this->getCurrentLineRecord();
       
        $this->_currentLine++;
       
        $parser = new \Gedcom\Parser\Object($this->_gedcom);
        
        $person->objects[] = $parser->parseFile($this->_file, $this->_currentLine, 1);

        $this->_errorLog += $parser->getErrors();
    }
    
    
    /**
     *
     */
    protected function parseBirtRecord(&$person)
    {
        $this->parseEventRecord($person, 'birth');
    }
    
    
    /**
     *
     */
    protected function parseChrRecord(&$person)
    {
        $this->parseEventRecord($person, 'christening');
    }
    
    
    /**
     *
     */
    protected function parseReference(&$reference, $atLevel)
    {
        $this->_currentLine++;
        
        while($this->_currentLine < count($this->_file))
        {
            $record = $this->getCurrentLineRecord();
            
            if((int)$record[0] <= (int)$atLevel)
            {
                $this->_currentLine--;
                
                return;
            }
            else if((int)$record[0] == ((int)$atLevel) + 1)
            {
                $recordType = trim($record[1]);
                
                switch($recordType)
                {
                    case 'PAGE':
                        $reference->page = trim($record[2]);
                    break;
                
                    case 'DATA':
                        $data = $reference->addData();
                        
                        $this->parseData($data, $record[0]);
                    break;
                    
                    case 'NOTE':
                        $note = $this->parseDataElementNote();
                        
                        if(is_a($note, "\\Gedcom\\Record\\Note"))
                        {
                            $reference->notes[] = $note;
                        }
                        else if(is_a($note, "\\Gedcom\\Record\\Note\\Reference"))
                        {
                            $reference->note_references[] = $note;
                        }
                    break;
                    
                    default:
                        $this->logUnhandledRecord(__LINE__);
                }
            }
            /*else if((int)$record[0] > (int)$atLevel)
            {
                // do nothing, this should be handled in cases above by
                // passing off code execution to other classes
            }*/
            else
            {
                $this->logUnhandledRecord(__LINE__);
            }
            
            $this->_currentLine++;
        }
    }
    
    
    /**
     *
     */
    protected function parseData(&$data, $atLevel)
    {
        $this->_currentLine++;
        
        while($this->_currentLine < count($this->_file))
        {
            $record = $this->getCurrentLineRecord();
            
            if((int)$record[0] <= (int)$atLevel)
            {
                $this->_currentLine--;
                
                return;
            }
            else if((int)$record[0] > ((int)$atLevel))
            {
                $recordType = trim($record[1]);
                
                switch($recordType)
                {
                    case 'TEXT':
                        $data->text = trim($record[2]);
                    break;
                    
                    case 'CONT':
                        $data->text .= "\n" . (isset($record[2]) ? trim($record[2]) : '');
                    break;
                    
                    case 'CONC':
                        $data->text .= trim($record[2]);
                    break;
                    
                    default:
                        $this->logUnhandledRecord(__LINE__);
                }
            }
            /*else if((int)$record[0] > (int)$atLevel)
            {
                // do nothing, this should be handled in cases above by
                // passing off code execution to other classes
            }*/
            else
            {
                $this->logUnhandledRecord(__LINE__);
            }
            
            $this->_currentLine++;
        }
    }
    
    
    /**
     *
     */
    protected function parseBapmRecord(&$person)
    {
        $this->parseEventRecord($person, 'baptism');
    }
    
    
    /**
     *
     */
    protected function parseDeatRecord(&$person)
    {
        $this->parseEventRecord($person, 'death', array('CAUS' => 'cause'));
    }
    
    
    /**
     *
     */
    protected function parseBuriRecord(&$person)
    {
        $this->parseEventRecord($person, 'burial');
    }
    
    
    /**
     *
     */
    protected function parseEducRecord(&$person)
    {
        $this->parseEventRecord($person, 'education');
    }
    
    
    /**
     *
     */
    protected function parseOccuRecord(&$person)
    {
        $this->parseEventRecord($person, 'occupation');
    }
    
    
    /**
     *
     *
     */
    protected function parseEventRecord(&$person, $eventType = null, $additionalAttr = array())
    {
        $this->_currentLine++;
        
        $event = $person->addEvent($eventType);
        
        while($this->_currentLine < count($this->_file))
        {
            $record = $this->getCurrentLineRecord();
            
            if((int)$record[0] < 2)
            {
                $this->_currentLine--;
                
                break;
            }
            else if($record[0] == '2')
            {
                $recordType = trim($record[1]);
                
                switch($recordType)
                {
                    case 'TYPE':
                        $event->type = trim($record[2]);
                    break;
                    
                    case 'DATE':
                        $event->date = trim($record[2]);
                    break;
                    
                    case 'PLAC':
                        if(!empty($record[2]))
                            $event->place = trim($record[2]);
                    break;
                    
                    case 'SOUR':
                        $reference = $this->_gedcom->createReference($this->normalizeIdentifier($record[2], 'S'), $eventType);
                        
                        $this->parseReference($reference, $record[0]);
                        
                        $event->addReference($reference);
                    break;
                    
                    case 'NOTE':
                        $note = $this->parseDataElementNote();
                        
                        if(is_a($note, "\\Gedcom\\Record\\Note"))
                        {
                            $event->notes[] = $note;
                        }
                        else if(is_a($note, "\\Gedcom\\Record\\Note\\Reference"))
                        {
                            $event->note_references[] = $note;
                        }
                    break;
                    
                    default:
                        if(isset($additionalAttr[$recordType]))
                            $event->$additionalAttr[$recordType] = trim($record[2]);
                        else
                            $this->logUnhandledRecord(__LINE__);
                }
            }
            /*else if((int)$record[0] > 2)
            {
                // do nothing, this should be handled in cases above by
                // passing off code execution to other classes
            }*/
            else
            {
                $this->logUnhandledRecord( __LINE__);
            }
            
            $this->_currentLine++;
        }
    }
    
    
    /**
     *
     */
    protected function parseCensRecord(&$person)
    {
        $this->parseEventRecord($person, 'census');
    }
    
    
    /**
     *
     */
    protected function parseEvenRecord(&$person)
    {
        $this->parseEventRecord($person, 'unknown');
    }
    
    
    /**
     *
     */
    protected function parseResiRecord(&$person)
    {
        $this->parseEventRecord($person, 'residence');
    }
    
    
    /**
     *
     */
    protected function parseImmiRecord(&$person)
    {
        $this->parseEventRecord($person, 'immigration');
    }
    
    
    /**
     *
     */
    protected function parsePropRecord(&$person)
    {
        $this->parseEventRecord($person, 'property');
    }
    
    
    /**
     *
     */
    protected function parseNoteRecord(&$person, $info, $level)
    {
        $record = $this->getCurrentLineRecord();
        
        if(isset($record[2]) && preg_match('/\@N([0-9]*)\@/i', $info) > 0)
        {
            $person->addNote($this->normalizeIdentifier($info, 'N'));
        }
        else
        {
            //$note = $person->addInternalNote($info);

            $note = new \Gedcom\Record\Note(); //$this->_gedcom->createNote();
            $this->parseNote($note);

            $person->addInternalNote($note);

            /*$this->_currentLine++;

            while($this->_currentLine < count($this->_file))
            {
                $record = $this->getCurrentLineRecord();

                if((int)$record[0] <= $level)
                {
                    $this->_currentLine--;
                    break;
                }
                else if(isset($record[1]) && trim($record[1]) == 'CONT')
                {
                    $note .= "\n" . (isset($record[2]) ? trim($record[2]) : '');
                }
                else if(isset($record[1]) && trim($record[1]) == 'CONC')
                {
                    $note .= (isset($record[2]) ? trim($record[2]) : '');
                }
                else
                {
                    $this->logUnhandledRecord(__LINE__);
                }

                $this->_currentLine++;
            }*/
        }
    }

}

