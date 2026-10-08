@props(['roles', 'selected'])


@foreach ($roles as $role)
<option value="{{ $role->id }}" @selected($role->id === $selected)>{{ __(ucfirst($role->name)) }}</option>
@endforeach
