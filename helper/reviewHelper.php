<?php


class ReviewHelper
{
    var $_skipUsers;
    var $_effort = 0;
    var $configuration;

    /**
     * ReviewHelper constructor.
     * @param string[] $configuration
     */
    public function __construct($configuration)
    {
        $this->configuration = $configuration;
        $this->_skipUsers = $configuration['skipUser'];
    }

    /**
     * @param $userStory
     * @param string[] $skipUsers
     * @return string
     */
    protected function getAssignedUsers($userStory, $skipUsers)
    {
        $assignedUsers = [];
        $assignedUsersFromUserStory = $userStory['AssignedUser']['Items'];

        foreach ($assignedUsersFromUserStory as $assignedUser) {
            $name = trim($assignedUser['FirstName'] . ' ' . $assignedUser['LastName']);
            if (in_array($name, $skipUsers)) {
                continue;
            }
            $assignedUsers[] = $name;
        }
        return implode(', ', $assignedUsers);
    }

    /**
     * @param string $entityName
     * @return string
     */
    protected function getStatusMarkup($entityName)
    {
        switch ($entityName) {
            case 'Done':
                $color = 'GREEN';
                break;
            case 'Approval':
                $color = 'GREEN';
                break;
            case 'In Testing':
                $color = 'BLUE';
                break;
            case 'Waiting for Feedback':
                $color = 'YELLOW';
                break;
            case 'Awaiting Deployment':
                $color = 'BLUE';
                break;
            case 'In Progress';
                $color = 'YELLOW';
                break;
            case 'Open';
                $color = 'RED';
                break;
            default:
                $color = 'WHITE';
                break;
        }

        $title = strtoupper($entityName);
        return "{status:colour={$color}|title={$title}|subtle=false}";
    }

    /**
     * @param string $entityName
     * @return string
     */
    protected function getColor($entityName)
    {
        switch ($entityName) {
            case 'Done':
                return 'green';
            case 'In Testing';
                return 'yellow';
            case 'In Progress';
                return 'blue';
            case 'Open';
                return 'grey';
        }
    }


    //formats rows into a confluence table
    /**
     * @param $array
     * @param bool $topicRow
     * @return string
     */
    public function formatTableRow($array, $topicRow = false)
    {
        if ($topicRow)
            return $tableRow = '|| {color:#CD6600} *' . implode('* {color} || {color:#CD6600} *', $array) . '* {color} ||<br>
            ';
        else
            return $tableRow = '| ' . implode(' | ', $array) . ' |<br>
            ';
    }

    /**
     * generates 1 array (row) each time
     * @param string[][] $entity
     * @return string
     */
    protected function _generateOutputForEntity($entity)
    {
        $content = "";

        $colorMarkUp = $this->getStatusMarkup($entity['EntityState']['Name']);
        $this->_effort += $entity['Effort'];
        $assignedUser = $this->getAssignedUsers($entity, $this->_skipUsers);

        $printArray = [
            "[#{$entity['Id']}|{$this->configuration['targetprocess_url']}{$entity['Id']}]",
            str_replace("|", ", ", "{$entity['Name']}"),
            "{$colorMarkUp}",
            "{$entity['Effort']}",
            "{$assignedUser}",
            "",
            ""
        ];
        $content = $content . $this->formatTableRow($printArray);
        return $content;
    }

    //puts all rows together
    /**
     * @param string[] $informationArray
     * @param string[]|null $bugArray
     * @return string
     */
    public function generateOutputForEntities(array $informationArray, array $bugArray = null)
    {

        $content = "";
        $count = 0;

        foreach ($informationArray as $sprint) {

            $this->_effort = 0;

            $information = $sprint['Information'];

            $content = $content . "
            || " . $sprint['Name'] . "||<br><br>
            
            ";

            $printArray = ["Link", "Title", "Status", "Effort", "Responsible", "Presentable", "Presentation Notes"];
            $content = $content . $this->formatTableRow($printArray, true);

            foreach ($information as $entity)
                $content = $content . $this->_generateOutputForEntity($entity);

            if ($bugArray != null){
                $sprint = $bugArray[$count++];

                $information = $sprint['Information'];

                if(count($information) != 0) {

                    $content = $content . " || ||{color:#CD6600} *BUGS* {color}|| || || || || ||<br>";

                    foreach ($information as $entity)
                        $content = $content . $this->_generateOutputForEntity($entity);

                }
            }
            $content = $content . "| | | |" . $this->_effort . "| | | |<br><br>
                ";
        }


        return $content;
    }
}