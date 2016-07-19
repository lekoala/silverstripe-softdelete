<?php
if (!class_exists('GridFieldBulkActionHandler')) {
    return;
}

class GridFieldBulkSoftDeleteEventHandler extends GridFieldBulkActionHandler
{
    /**
     * RequestHandler allowed actions.
     *
     * @var array
     */
    private static $allowed_actions = array('softDelete');

    /**
     * RequestHandler url => action map.
     *
     * @var array
     */
    private static $url_handlers = array(
        'softDelete' => 'softDelete',
    );

    /**
     * Mark as deleted
     *
     * @param SS_HTTPRequest $request
     *
     * @return SS_HTTPResponse
     */
    public function softDelete(SS_HTTPRequest $request)
    {
        $ids = array();

        foreach ($this->getRecords() as $record) {
            array_push($ids, $record->ID);
            $record->softDelete();
        }

        $response = new SS_HTTPResponse(Convert::raw2json(array(
                'done' => true,
                'records' => $ids,
        )));
        $response->addHeader('Content-Type', 'text/json');

        return $response;
    }
}