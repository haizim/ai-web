<?php

declare(strict_types=1);

namespace App\Livewire\Table;

use App\Models\Style;
use Laravolt\Suitable\Columns\Date;
use Laravolt\Suitable\Columns\Id;
use Laravolt\Suitable\Columns\RestfulButton;
use Laravolt\Suitable\Columns\Text;
use Laravolt\Ui\TableView;

final class StyleTable extends TableView
{
    public function data()
    {
        $query = Style::query()->where('user_id', auth()->id());

        if ($this->sortPayload()['sort']) {
            $query = $query->autoSort($this->sortPayload());
        } else {
            $query = $query->orderBy('updated_at', 'desc');
        }

        if ($this->search) {
            $query->whereLike(['name'], $this->search);
        }

        return $query->paginate($this->perPage);
    }

    public function columns(): array
    {
        return [
            Id::make('id', 'ID')->sortable(),
            Text::make('name', 'Name')->sortable(),
            Date::make('created_at', 'Created At')->sortable(),
            Date::make('updated_at', 'Updated At')->sortable(),
            RestfulButton::make('styles')->only(['show', 'edit', 'destroy'])
                ->setCellAttributes(['style' => 'text-align: right; width: 2em;']),
        ];
    }
}
