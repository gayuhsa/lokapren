<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AddressModel;

/**
 * The customer's address book.
 *
 * Geography is free text: village, district, regency and province are whatever
 * the customer types, with no lookup table behind them. `latitude`/`longitude`
 * are optional and only feed the map preview, so an address can be saved without
 * ever placing a pin.
 *
 * `user_id` is never read from the request — `findOwnedBy()` scopes every read
 * and the writes set the owner explicitly.
 */
class AddressController extends BaseController
{
    public function index()
    {
        $userId = $this->requireUserId();
        $model  = new AddressModel();

        return view('account/addresses', [
            'title'      => 'Alamat Saya',
            'addresses'  => $model->forUser($userId),
            'cart_count' => $this->cartCount(),
        ]);
    }

    public function create()
    {
        return view('account/address_form', [
            'title'     => 'Tambah Alamat',
            'address'   => null,
            'action'    => route_to('address_store'),
            'cart_count' => $this->cartCount(),
        ]);
    }

    public function edit(int $id)
    {
        $userId  = $this->requireUserId();
        $address = (new AddressModel())->findOwnedBy($id, $userId);

        if ($address === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view('account/address_form', [
            'title'     => 'Ubah Alamat',
            'address'   => $address,
            'action'    => route_to('address_update', $id),
            'cart_count' => $this->cartCount(),
        ]);
    }

    public function store()
    {
        $userId = $this->requireUserId();
        $data   = $this->validatedAddress();

        if ($data === null) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $model   = new AddressModel();
        $now     = date('Y-m-d H:i:s');
        $isFirst = $model->forUser($userId) === [];

        $data['user_id']    = $userId;
        $data['is_default'] = $isFirst ? 1 : 0;
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        $id = $model->insertRow($data);

        if ($isFirst) {
            $model->newQuery()->where('id', $id)->set('is_default', 1)->update();
        }

        return redirect()->route('addresses')->with('success', 'Alamat disimpan.');
    }

    public function update(int $id)
    {
        $userId  = $this->requireUserId();
        $model   = new AddressModel();
        $address = $model->findOwnedBy($id, $userId);

        if ($address === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $data = $this->validatedAddress();

        if ($data === null) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data['updated_at'] = date('Y-m-d H:i:s');

        $model->updateWhere($data, ['id' => $id, 'user_id' => $userId]);

        if ($this->request->getPost('is_default') === '1') {
            $this->makeDefault($userId, $id);
        }

        return redirect()->route('addresses')->with('success', 'Alamat diperbarui.');
    }

    public function destroy(int $id)
    {
        $userId = $this->requireUserId();
        $model  = new AddressModel();

        if ($model->findOwnedBy($id, $userId) === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // Deleted with an owner-scoped condition rather than a bare id.
        $model->newQuery()->where('id', $id)->where('user_id', $userId)->delete();

        // The deleted row may have carried the default flag; exactly one
        // address per user stays default, so promote the oldest remaining.
        $defaultExists = $model->newQuery()
            ->where('user_id', $userId)
            ->where('is_default', 1)
            ->countAllResults() > 0;

        if (! $defaultExists) {
            $next = $model->newQuery()
                ->where('user_id', $userId)
                ->orderBy('id')
                ->limit(1)
                ->get()
                ->getResultArray();

            if ($next !== []) {
                $this->promote($userId, (int) $next[0]['id']);
            }
        }

        return redirect()->route('addresses')->with('success', 'Alamat dihapus.');
    }

    public function makeDefault(int $id)
    {
        $userId = $this->requireUserId();

        if ((new AddressModel())->findOwnedBy($id, $userId) === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $this->promote($userId, $id);

        return redirect()->route('addresses')->with('success', 'Alamat utama diperbarui.');
    }

    /**
     * Exactly one address per user carries the default flag, enforced by clearing
     * the others first inside a transaction.
     */
    private function promote(int $userId, int $id): void
    {
        $model = new AddressModel();
        $db    = db_connect();

        $db->transStart();

        $model->newQuery()->where('user_id', $userId)->set('is_default', 0)->update();
        $model->newQuery()->where('id', $id)->where('user_id', $userId)->set('is_default', 1)->update();

        if ($db->transStatus() === false) {
            $db->transRollback();

            return;
        }

        $db->transCommit();
    }

    /**
     * Validate and normalise the posted address, or null when it fails.
     *
     * @return array<string, mixed>|null
     */
    private function validatedAddress(): ?array
    {
        $rules = [
            'label'           => 'required|max_length[40]',
            'recipient_name'  => 'required|max_length[150]',
            'recipient_phone' => 'required|max_length[25]',
            'address_line'    => 'required|max_length[255]',
            'village'         => 'permit_empty|max_length[100]',
            'district'        => 'permit_empty|max_length[100]',
            'regency'         => 'permit_empty|max_length[100]',
            'province'        => 'permit_empty|max_length[100]',
            'postal_code'     => 'permit_empty|max_length[10]',
            'latitude'        => 'permit_empty|numeric|greater_than_equal_to[-90]|less_than_equal_to[90]',
            'longitude'       => 'permit_empty|numeric|greater_than_equal_to[-180]|less_than_equal_to[180]',
            'landmark'        => 'permit_empty|max_length[150]',
            'delivery_notes'  => 'permit_empty|max_length[255]',
            'is_hotel'        => 'permit_empty|in_list[0,1]',
            'is_default'      => 'permit_empty|in_list[0,1]',
        ];

        if (! $this->validate($rules)) {
            return null;
        }

        // Coordinates are only kept when both halves are present; a lone value
        // would put a pin in the ocean.
        $latitude  = $this->nullableFloat($this->request->getPost('latitude'));
        $longitude = $this->nullableFloat($this->request->getPost('longitude'));

        return [
            'label'           => trim((string) $this->request->getPost('label')),
            'recipient_name'  => trim((string) $this->request->getPost('recipient_name')),
            'recipient_phone' => trim((string) $this->request->getPost('recipient_phone')),
            'address_line'    => trim((string) $this->request->getPost('address_line')),
            'village'         => trim((string) $this->request->getPost('village')) ?: null,
            'district'        => trim((string) $this->request->getPost('district')) ?: null,
            'regency'         => trim((string) $this->request->getPost('regency')) ?: null,
            'province'        => trim((string) $this->request->getPost('province')) ?: null,
            'postal_code'     => trim((string) $this->request->getPost('postal_code')) ?: null,
            'latitude'        => ($latitude !== null && $longitude !== null) ? $latitude : null,
            'longitude'       => ($latitude !== null && $longitude !== null) ? $longitude : null,
            'landmark'        => trim((string) $this->request->getPost('landmark')) ?: null,
            'delivery_notes'  => trim((string) $this->request->getPost('delivery_notes')) ?: null,
            'is_hotel'        => $this->request->getPost('is_hotel') === '1' ? 1 : 0,
        ];
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}
