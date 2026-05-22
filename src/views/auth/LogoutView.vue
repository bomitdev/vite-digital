<script>
import auth from './services/auth';

export default {
  async mounted() {
    await auth.logout();
    this.$router.replace('/login');
  }
};
</script>
