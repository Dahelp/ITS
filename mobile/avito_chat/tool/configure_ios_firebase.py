"""Import a registered Firebase iOS app. Never accepts service-account keys."""
import argparse
import json
import os
from pathlib import Path
import plistlib
import sys

BUNDLE = 'ru.itscenter.itsAvito'
PROJECT = 'its-avito'


def configure(source, root):
    raw = Path(source).read_bytes()
    data = plistlib.loads(raw)
    if data.get('BUNDLE_ID') != BUNDLE or data.get('PROJECT_ID') != PROJECT:
        raise ValueError('Firebase plist must match its-avito / ru.itscenter.itsAvito')
    for name in ('GOOGLE_APP_ID', 'API_KEY', 'GCM_SENDER_ID'):
        if not isinstance(data.get(name), str) or not data[name].strip():
            raise ValueError('Missing Firebase client setting: ' + name)
    if not data['GOOGLE_APP_ID'].startswith('1:' + data['GCM_SENDER_ID'] + ':ios:'):
        raise ValueError('Firebase iOS app ID and sender ID do not match')
    root = Path(root)
    config = root / 'firebase.local.json'
    current = json.loads(config.read_text(encoding='utf-8-sig')) if config.exists() else json.loads((root / 'firebase.example.json').read_text(encoding='utf-8-sig'))
    if current.get('FIREBASE_PROJECT_ID') not in ('', PROJECT):
        raise ValueError('Existing Firebase configuration belongs to another project')
    if current.get('FIREBASE_SENDER_ID') not in ('', data['GCM_SENDER_ID']):
        raise ValueError('Existing Firebase configuration has a different sender ID')
    current.update(PUSH_ENABLED=True, FIREBASE_PROJECT_ID=PROJECT,
                   FIREBASE_SENDER_ID=data['GCM_SENDER_ID'],
                   FIREBASE_IOS_APP_ID=data['GOOGLE_APP_ID'],
                   FIREBASE_IOS_API_KEY=data['API_KEY'])
    target = root / 'ios/Runner/GoogleService-Info.plist'
    target.parent.mkdir(parents=True, exist_ok=True)
    target.write_bytes(raw)
    config.write_text(json.dumps(current, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
    os.chmod(target, 0o600)
    os.chmod(config, 0o600)


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('plist', help='Downloaded GoogleService-Info.plist')
    args = parser.parse_args()
    try:
        configure(args.plist, Path(__file__).resolve().parent.parent)
    except (ValueError, OSError, plistlib.InvalidFileException):
        print('Firebase iOS configuration rejected. Check project, bundle ID and client settings.', file=sys.stderr)
        sys.exit(1)
    print('Firebase iOS client configured; Android settings preserved. APNs and signing must be configured separately.')
