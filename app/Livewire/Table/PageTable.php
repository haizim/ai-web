<?php

namespace App\Livewire\Table;

use App\Models\Page;
use Laravolt\Suitable\Columns\Label;
use Laravolt\Suitable\Columns\Raw;
use Laravolt\Suitable\Columns\RowNumber;
use Laravolt\Suitable\Columns\RestfulButton;
use Laravolt\Suitable\Columns\Text;
use Laravolt\Ui\TableView;

class PageTable extends TableView
{
    public function data()
    {
        $query = Page::query()->where('user_id', auth()->user()->id);

        if ($this->sortPayload()['sort']) {
            $query = $query->autoSort($this->sortPayload());
        } else {
            $query = $query->orderBy('updated_at', 'desc');
        }

        $search = $this->search;
        if ($search) {
            $query->whereLike(['judul'], $search);
        }

        return $query->paginate($this->perPage);
    }

    public function columns(): array
    {
        return [
            RowNumber::make('no'),
            Text::make('judul')->sortable(),
            Text::make('slug')->sortable(),
            Label::make('status')->sortable(),
            Raw::make(
                function ($data) {
                    $url = route('p.show', $data->slug);
                    
                    return  "<a href='$url' target='_blank' class='ui tiny black icon secondary button'><i class='eye icon'></i></a>";
                }
            )->setCellAttributes(['style' => "text-align: right; width: 50px;"]),
            RestfulButton::make('page')->only(['edit', 'destroy'])->setCellAttributes(['style' => 'text-align: right; width: 2em;'])
        ];
    }
}
