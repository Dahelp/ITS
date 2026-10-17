import json
from pathlib import Path
import plistlib
import tempfile
import unittest
from configure_ios_firebase import configure


class FirebaseIosConfigTest(unittest.TestCase):
    def test_matching_app_preserves_android_settings(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            original = {'FIREBASE_PROJECT_ID': 'its-avito', 'FIREBASE_SENDER_ID': '123',
                        'FIREBASE_ANDROID_APP_ID': 'android-client', 'FIREBASE_ANDROID_API_KEY': 'android-public-key'}
            (root / 'firebase.local.json').write_text(json.dumps(original))
            source = root / 'client.plist'
            source.write_bytes(plistlib.dumps({'BUNDLE_ID': 'ru.itscenter.itsAvito',
                'PROJECT_ID': 'its-avito', 'GCM_SENDER_ID': '123', 'GOOGLE_APP_ID': '1:123:ios:abc', 'API_KEY': 'ios-public-key'}))
            configure(source, root)
            result = json.loads((root / 'firebase.local.json').read_text())
            self.assertEqual(result['FIREBASE_ANDROID_APP_ID'], original['FIREBASE_ANDROID_APP_ID'])
            self.assertEqual(result['FIREBASE_ANDROID_API_KEY'], original['FIREBASE_ANDROID_API_KEY'])
            self.assertTrue(result['PUSH_ENABLED'])
            self.assertEqual(result['FIREBASE_IOS_APP_ID'], '1:123:ios:abc')
            self.assertEqual((root / 'ios/Runner/GoogleService-Info.plist').read_bytes(), source.read_bytes())

    def test_wrong_project_or_bundle_does_not_write_configuration(self):
        for override in [{'BUNDLE_ID': 'another.app'}, {'PROJECT_ID': 'another-project'}, {'GOOGLE_APP_ID': '1:999:ios:abc'}]:
            with self.subTest(override=override), tempfile.TemporaryDirectory() as folder:
                root = Path(folder)
                source = root / 'client.plist'
                data = {'BUNDLE_ID': 'ru.itscenter.itsAvito', 'PROJECT_ID': 'its-avito',
                        'GCM_SENDER_ID': '123', 'GOOGLE_APP_ID': '1:123:ios:abc', 'API_KEY': 'public-key'}
                source.write_bytes(plistlib.dumps(dict(data, **override)))
                with self.assertRaises(ValueError):
                    configure(source, root)
                self.assertFalse((root / 'ios/Runner/GoogleService-Info.plist').exists())
                self.assertFalse((root / 'firebase.local.json').exists())


if __name__ == '__main__':
    unittest.main()
