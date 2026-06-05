import { useEffect, useState } from 'react';
import { listUsers } from '../../api/users';
import { Select } from './Field';

export default function UserPicker({ id, value, onChange, role, includeBlank = true, blankLabel = '— None —' }) {
    const [users, setUsers] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        let mounted = true;
        const params = { per_page: 100 };
        if (role) params.role = role;
        listUsers(params)
            .then((res) => {
                if (!mounted) return;
                // /users returns either { data: [...] } (admin paginated) or a raw array (others).
                const list = Array.isArray(res) ? res : (res?.data ?? []);
                setUsers(list);
            })
            .catch(() => setUsers([]))
            .finally(() => mounted && setLoading(false));
        return () => {
            mounted = false;
        };
    }, [role]);

    return (
        <Select id={id} value={value ?? ''} onChange={onChange} disabled={loading}>
            {includeBlank && <option value="">{loading ? 'Loading…' : blankLabel}</option>}
            {users.map((u) => (
                <option key={u.id} value={u.id}>
                    {u.name} · {u.email}
                </option>
            ))}
        </Select>
    );
}
