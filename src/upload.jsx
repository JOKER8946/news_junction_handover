import React, { useState } from 'react';
import { upload } from './api';
export default function ImageUpload({ onUpload }) {
  const [busy, setBusy] = useState(false),
    [error, setError] = useState('');
  return (
    <div className="upload-control">
      <label>
        {busy ? 'Uploading image…' : 'Or upload a cover image'}
        <input
          type="file"
          accept="image/jpeg,image/png,image/webp"
          disabled={busy}
          onChange={async (e) => {
            const file = e.target.files?.[0];
            if (!file) return;
            setBusy(true);
            setError('');
            try {
              onUpload(await upload(file));
            } catch (e) {
              setError(e.message);
            } finally {
              setBusy(false);
            }
          }}
        />
      </label>
      <small>JPEG, PNG, or WebP · up to 8 MB</small>
      {error && (
        <p className="form-error" role="alert">
          {error}
        </p>
      )}
    </div>
  );
}
