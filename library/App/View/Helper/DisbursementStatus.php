<?php

class App_View_Helper_DisbursementStatus extends Zend_View_Helper_Abstract
{
    private const MAP = [
        'waiting' => [
            'label' => 'Waiting',
            'label_2' => 'Waiting',
            'bg' => '#FFA700',
            'headerBg' => '#FFF6E6',
            'text' => '#FFA700',
            'text_2' => '#FFA700'
        ],
        'rejected' => [
            'label' => 'Rejected',
            'label_2' => 'Rejected',
            'bg' => '#E5484D',
            'headerBg' => '#FDEFEF',
            'text' => '#E5484D',
            'text_2' => '#E5484D'
        ],
        'process' => [
            'label' => 'Process',
            'label_2' => 'Approved',
            'bg' => '#3A7BF6',
            'headerBg' => '#EBF2FE',
            'text' => '#3A7BF6',
            'text_2' => '#3A7BF6'
        ],
        'completed' => [
            'label' => 'Completed',
            'label_2' => 'Approved',
            'bg' => '#21B531',
            'headerBg' => '#E8F7EA',
            'text' => '#21B531',
            'text_2' => '#3A7BF6'
        ],
        'canceled' => [
            'label' => 'Canceled',
            'label_2' => 'Approved',
            'bg' => '#12161C',
            'headerBg' => '#E7E8E8',
            'text' => '#FFFFFF',
            'text_2' => '#3A7BF6'
        ],
        'expired' => [
            'label' => 'Expired',
            'label_2' => 'Expired',
            'bg' => '#B849E1',
            'headerBg' => '#FAEFFE',
            'text' => '#B849E1',
            'text_2' => '#B849E1'
        ],
    ];

    /**
     * Resolve presentational metadata (label, colors) for a disbursement status.
     * Unknown statuses fall back to a neutral grey style.
     */
    public function disbursementStatus(?string $status): array
    {
        $key = strtolower(trim((string) $status));
        return self::MAP[$key] ?? [
            'label' => $key !== '' ? ucfirst($key) : '-',
            'bg' => '#6B7280',
            'headerBg' => '#F3F4F6',
            'text' => '#FFFFFF',
            'text_2' => '#000000ff',
        ];
    }
}
