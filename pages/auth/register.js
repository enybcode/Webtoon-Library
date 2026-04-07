import { useEffect, useState } from 'react';

export default function RegisterPage() {
  const [questions, setQuestions] = useState([]);
  const [form, setForm] = useState({
    username: '',
    email: '',
    password: '',
    securityQuestionId: '',
    securityAnswer: '',
  });
  const [message, setMessage] = useState('');

  useEffect(() => {
    fetch('/api/auth/questions')
      .then((res) => res.json())
      .then((data) => setQuestions(data.questions || []));
  }, []);

  function handleChange(e) {
    setForm({ ...form, [e.target.name]: e.target.value });
  }

  async function handleSubmit(e) {
    e.preventDefault();
    const res = await fetch('/api/auth/register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form),
    });

    const data = await res.json();
    setMessage(data.message);
    if (res.ok) {
      window.location.href = '/auth/login';
    }
  }

  return (
    <div>
      <h2>Inscription</h2>
      <form onSubmit={handleSubmit} className="form">
        <input name="username" placeholder="Pseudo" onChange={handleChange} required />
        <input name="email" type="email" placeholder="Email" onChange={handleChange} required />
        <input name="password" type="password" placeholder="Mot de passe" onChange={handleChange} required />
        <select name="securityQuestionId" onChange={handleChange} required>
          <option value="">Choisir une question secrète</option>
          {questions.map((q) => (
            <option value={q.id} key={q.id}>{q.question}</option>
          ))}
        </select>
        <input name="securityAnswer" placeholder="Réponse secrète" onChange={handleChange} required />
        <button type="submit">S'inscrire</button>
      </form>
      {message && <p>{message}</p>}
    </div>
  );
}
